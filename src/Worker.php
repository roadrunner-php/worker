<?php

/**
 * High-performance PHP process supervisor and load balancer written in Go.
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Spiral\Goridge\Exceptions\GoridgeException;
use Spiral\Goridge\RelayInterface as Relay;
use Spiral\Goridge\SendPackageRelayInterface;
use Spiral\RoadRunner\Exception\RoadRunnerException;

/**
 * Accepts connection from RoadRunner server over given Goridge relay.
 *
 * Example:
 *
 * $worker = new Worker(new Goridge\StreamRelay(STDIN, STDOUT));
 * while ($p = $worker->waitPayload()) {
 *      $worker->send(new Payload("DONE", json_encode($context)));
 * }
 */
class Worker implements WorkerInterface
{
    // Request graceful worker termination.
    private const STOP_REQUEST = '{"stop":true}';

    /** @var Relay */
    private Relay $relay;

    /**
     * @param Relay $relay
     */
    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }

    /**
     * Wait for incoming payload from the server. Must return null when worker stopped.
     *
     * @return Payload|null
     * @throws GoridgeException
     * @throws RoadRunnerException
     */
    public function waitPayload(): ?Payload
    {
        $header = null;
        if (!$this->waitHeader($header)) {
            return null;
        }

        return new Payload($this->relay->receiveSync(), $header);
    }

    /**
     * Respond to the server with the processing result.
     *
     * @param Payload $payload
     * @throws GoridgeException
     */
    public function respond(Payload $payload): void
    {
        $this->send($payload->body, $payload->header);
    }

    /**
     * Respond to the server with an error. Error must be treated as TaskError and might not cause
     * worker destruction.
     *
     * Example:
     *
     * $worker->error("invalid payload");
     *
     * @param string $message
     */
    public function error(string $message): void
    {
        $this->relay->send($message, Relay::PAYLOAD_ERROR);
    }

    /**
     * Terminate the process. Server must automatically pass task to the next available process.
     * Worker will receive StopCommand context after calling this method.
     *
     * Attention, you MUST use continue; after invoking this method to let rr to properly
     * stop worker.
     *
     * @throws GoridgeException
     */
    public function stop(): void
    {
        $this->send("", self::STOP_REQUEST);
    }

    /**
     * @param string      $body
     * @param string|null $context
     * @throws GoridgeException
     */
    public function send(string $body, ?string $context): void
    {
        if ($this->relay instanceof SendPackageRelayInterface) {
            $this->relay->sendPackage(
                (string) $context,
                Relay::PAYLOAD_CONTROL,
                (string) $body
            );
        }

        $this->relay->send($context, Relay::PAYLOAD_CONTROL);
        $this->relay->send($body);
    }

    /**
     * @param string|null $header
     * @return bool
     *
     * @throws GoridgeException
     * @throws RoadRunnerException
     */
    private function waitHeader(?string &$header): bool
    {
        $header = $this->relay->receiveSync($flags);
        if (!$flags & Relay::PAYLOAD_CONTROL) {
            // got the beginning of the frame
            return true;
        }

        if (!$this->handleControl($header)) {
            return false;
        }

        return $this->waitHeader($header);
    }

    /**
     * Return true if continue.
     *
     * @param string $header
     * @return bool
     *
     * @throws RoadRunnerException
     */
    private function handleControl(string $header): bool
    {
        $command = json_decode($header, true);
        if ($command === false) {
            throw new RoadRunnerException('Invalid task header, JSON payload is expected');
        }

        switch (true) {
            case !empty($p['pid']):
                $this->relay->send(sprintf('{"pid":%s}', getmypid()), Relay::PAYLOAD_CONTROL);
                return true;

            case !empty($p['stop']):
                return false;

            default:
                throw new RoadRunnerException('Invalid task header, undefined control package');
        }
    }
}
