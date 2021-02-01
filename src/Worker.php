<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Spiral\Goridge\Exception\GoridgeException;
use Spiral\Goridge\Frame;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Exception\EnvironmentException;
use Spiral\RoadRunner\Exception\RoadRunnerException;

/**
 * Accepts connection from RoadRunner server over given Goridge relay.
 *
 * $worker = Worker::create();
 * while ($p = $worker->waitPayload()) {
 *      $worker->send(new Payload("DONE", json_encode($context)));
 * }
 */
class Worker implements WorkerInterface
{
    // Request graceful worker termination.
    private const STOP_REQUEST = '{"stop":true}';

    /**
     * @var RelayInterface
     */
    private RelayInterface $relay;

    /**
     * @param RelayInterface $relay
     */
    public function __construct(RelayInterface $relay)
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
        $frame = $this->relay->waitFrame();

        if ($frame->hasFlag(Frame::CONTROL)) {
            $continue = $this->handleControl($frame->payload);

            return $continue ? $this->waitPayload() : null;
        }

        return new Payload(
            \substr($frame->payload, $frame->options[0]),
            \substr($frame->payload, 0, $frame->options[0])
        );
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
     * @param string $error
     */
    public function error(string $error): void
    {
        $this->relay->send(new Frame($error, [], Frame::ERROR));
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
        $this->send('', self::STOP_REQUEST);
    }

    /**
     * @param string      $body
     * @param string|null $context
     * @throws GoridgeException
     */
    public function send(string $body, string $context = null): void
    {
        $frame = new Frame($context . $body, [
            \strlen((string) $context)
        ]);

        $this->relay->send($frame);
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
        try {
            $command = \json_decode($header, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RoadRunnerException('Invalid task header, JSON payload is expected: ' . $e->getMessage());
        }

        switch (true) {
            case !empty($command['pid']):
                $this->relay->send(new Frame(sprintf('{"pid":%s}', getmypid()), [], Frame::CONTROL));
                return true;

            case !empty($command['stop']):
                return false;

            default:
                throw new RoadRunnerException('Invalid task header, undefined control package');
        }
    }

    /**
     * Create {@see Worker} using global environment ({@see Environment}) configuration.
     *
     * @return WorkerInterface
     * @throws EnvironmentException
     */
    public static function create(): WorkerInterface
    {
        $env = Environment::fromGlobals();

        return new static(Relay::create($env->getRelayAddress()));
    }
}
