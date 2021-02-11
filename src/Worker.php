<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use JetBrains\PhpStorm\Deprecated;
use Spiral\Goridge\Exception\GoridgeException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Frame;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Exception\EnvironmentException;
use Spiral\RoadRunner\Exception\RoadRunnerException;

/**
 * Accepts connection from RoadRunner server over given Goridge relay.
 *
 * <code>
 * $worker = Worker::create();
 *
 * while ($receivedPayload = $worker->waitPayload()) {
 *      $worker->send(new Payload("DONE", json_encode($context)));
 * }
 * </code>
 */
class Worker implements WorkerInterface
{
    /**
     * @var int
     */
    private const JSON_ENCODE_FLAGS = \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @var int
     */
    private const JSON_DECODE_FLAGS = \JSON_THROW_ON_ERROR;

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
     * {@inheritDoc}
     */
    public function waitPayload(): ?Payload
    {
        $frame = $this->relay->waitFrame();

        $payload = $frame->payload ?? '';

        if ($frame->hasFlag(Frame::CONTROL)) {
            $continue = $this->handleControl($payload);

            return $continue ? $this->waitPayload() : null;
        }

        return new Payload(
            \substr($payload, $frame->options[0]),
            \substr($payload, 0, $frame->options[0])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function respond(Payload $payload): void
    {
        $this->sendRaw($payload->body, $payload->header);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $error): void
    {
        $frame = new Frame($error, [], Frame::ERROR);

        $this->sendFrame($frame);
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        $this->sendRaw('', $this->encode(['stop' => true]));
    }

    /**
     * {@inheritDoc}
     */
    #[Deprecated(replacement: '%class%->respond(new Payload(%parameter0%, %parameter1%))')]
    public function send(string $body = null, string $header = null): void
    {
        $this->sendRaw($body ?? '', $header ?? '');
    }

    /**
     * @param string $body
     * @param string $header
     */
    private function sendRaw(string $body = '', string $header = ''): void
    {
        $frame = new Frame($header . $body, [\strlen($header)]);

        $this->sendFrame($frame);
    }

    /**
     * @param Frame $frame
     */
    private function sendFrame(Frame $frame): void
    {
        try {
            $this->relay->send($frame);
        } catch (GoridgeException $e) {
            throw new TransportException($e->getMessage(), (int)$e->getCode(), $e);
        } catch (\Throwable $e) {
            throw new RoadRunnerException($e->getMessage(), (int)$e->getCode(), $e);
        }
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
            $command = $this->decode($header);
        } catch (\JsonException $e) {
            throw new RoadRunnerException('Invalid task header, JSON payload is expected: ' . $e->getMessage());
        }

        switch (true) {
            case !empty($command['pid']):
                $frame = new Frame($this->encode(['pid' => \getmypid()]), [], Frame::CONTROL);
                $this->sendFrame($frame);
                return true;

            case !empty($command['stop']):
                return false;

            default:
                throw new RoadRunnerException('Invalid task header, undefined control package');
        }
    }

    /**
     * @param string $json
     * @return array
     * @throws \JsonException
     */
    private function decode(string $json): array
    {
        $result = \json_decode($json, true, 512, self::JSON_DECODE_FLAGS);

        if (! \is_array($result)) {
            throw new \JsonException('Json message must be an array or object');
        }

        return $result;
    }

    /**
     * @param array $payload
     * @return string
     */
    private function encode(array $payload): string
    {
        return \json_encode($payload, self::JSON_ENCODE_FLAGS);
    }

    /**
     * Create a new RoadRunner {@see Worker} using global
     * environment ({@see Environment}) configuration.
     *
     * @return self
     * @throws EnvironmentException
     */
    public static function create(): self
    {
        return static::createFromEnvironment(
            Environment::fromGlobals()
        );
    }

    /**
     * Create a new RoadRunner {@see Worker} using passed
     * environment configuration.
     *
     * @param EnvironmentInterface $env
     * @return self
     */
    public static function createFromEnvironment(EnvironmentInterface $env): self
    {
        return new self(Relay::create($env->getRelayAddress()));
    }
}
