<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Psr\Log\LoggerInterface;
use Spiral\Goridge\Exception\GoridgeException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Frame;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Exception\RoadRunnerException;
use Spiral\RoadRunner\Internal\StdoutHandler;
use Spiral\RoadRunner\Message\Command\GetProcessId;
use Spiral\RoadRunner\Message\Command\WorkerStop;

/**
 * Accepts connection from RoadRunner server over given Goridge relay.
 *
 * <code>
 * $worker = Worker::create();
 *
 * while ($receivedPayload = $worker->waitPayload()) {
 *      $worker->respond(new Payload("DONE", json_encode($context)));
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
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /** @var array<int, Payload> */
    private array $payloads = [];

    /**
     * @param RelayInterface $relay
     * @param bool $interceptSideEffects
     */
    public function __construct(RelayInterface $relay, bool $interceptSideEffects = true)
    {
        $this->relay = $relay;
        $this->logger = new Logger();

        if ($interceptSideEffects) {
            StdoutHandler::register();
        }
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritDoc}
     */
    public function waitPayload(): ?Payload
    {
        if ($this->payloads !== []) {
            $payload = \array_shift($this->payloads);
        } else {
            $frame = $this->relay->waitFrame();
            $payload = PayloadFactory::fromFrame($frame);
        }

        return match (true) {
            $payload instanceof GetProcessId => $this->sendProcessId()->waitPayload(),
            $payload instanceof WorkerStop => null,
            default => $payload,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function respond(Payload $payload): void
    {
        $this->send($payload->body, $payload->header, $payload->eos);
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
        $this->send('', $this->encode(['stop' => true]));
    }

    /**
     * {@inheritDoc}
     */
    public function hasPayload(string $class = null): bool
    {
        return $this->findPayload($class) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPayload(string $class = null): ?Payload
    {
        $pos = $this->findPayload($class);
        if ($pos === null) {
            return null;
        }
        $result = $this->payloads[$pos];
        unset($this->payloads[$pos]);

        return $result;
    }

    /**
     * @param class-string<Payload>|null $class
     *
     * @return null|int Index in {@see $this->payloads} or null if not found
     */
    private function findPayload(string $class = null): ?int
    {
        // Find in existing payloads
        if ($this->payloads !== []) {
            if ($class === null) {
                return \array_key_first($this->payloads);
            }

            foreach ($this->payloads as $pos => $payload) {
                if ($payload::class === $class) {
                    return $pos;
                }
            }
        }

        do {
            if ($class === null && $this->payloads !== []) {
                return \array_key_first($this->payloads);
            }

            $payload = $this->pullPayload();
            if ($payload === null) {
                break;
            }

            $this->payloads[] = $payload;
            if ($class !== null && $payload::class === $class) {
                return \array_key_last($this->payloads);
            }
        } while (true);

        return null;
    }

    /**
     * Pull {@see Payload} if it is available without blocking.
     *
     * @return null|Payload
     */
    private function pullPayload(): ?Payload
    {
        if (!$this->relay->hasFrame()) {
            return null;
        }

        $frame = $this->relay->waitFrame();
        return PayloadFactory::fromFrame($frame);
    }

    /**
     * @param bool $eos End of stream
     */
    private function send(string $body = '', string $header = '', bool $eos = true): void
    {
        $frame = new Frame($header . $body, [\strlen($header)]);

        if (!$eos) {
            $frame->byte10 = Frame::BYTE10_STREAM;
        }

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
     * @param bool $interceptSideEffects
     * @return self
     */
    public static function create(bool $interceptSideEffects = true): self
    {
        return static::createFromEnvironment(Environment::fromGlobals(), $interceptSideEffects);
    }

    /**
     * Create a new RoadRunner {@see Worker} using passed environment
     * configuration.
     *
     * @param EnvironmentInterface $env
     * @param bool $interceptSideEffects
     * @return self
     */
    public static function createFromEnvironment(EnvironmentInterface $env, bool $interceptSideEffects = true): self
    {
        return new self(Relay::create($env->getRelayAddress()), $interceptSideEffects);
    }

    private function sendProcessId(): static
    {
        $frame = new Frame($this->encode(['pid' => \getmypid()]), [], Frame::CONTROL);
        $this->sendFrame($frame);
        return $this;
    }
}
