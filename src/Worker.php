<?php

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
use Spiral\RoadRunner\Message\SkipMessage;

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
    private const JSON_ENCODE_FLAGS = \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION;
    private const JSON_DECODE_FLAGS = \JSON_THROW_ON_ERROR;

    private RelayInterface $relay;

    private LoggerInterface $logger;

    /** @var array<int, Payload> */
    private array $payloads = [];

    public function __construct(RelayInterface $relay, bool $interceptSideEffects = true)
    {
        $this->relay = $relay;
        $this->logger = new Logger();

        if ($interceptSideEffects) {
            StdoutHandler::register();
        }
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function waitPayload(): ?Payload
    {
        while (true) {
            if ($this->payloads !== []) {
                $payload = \array_shift($this->payloads);
            } else {
                $frame = $this->relay->waitFrame();
                $payload = PayloadFactory::fromFrame($frame);
            }

            switch (true) {
                case $payload::class === Payload::class:
                    return $payload;
                case $payload instanceof WorkerStop:
                    return null;
                case $payload::class === GetProcessId::class:
                    $this->sendProcessId();
                    // no break
                case $payload instanceof SkipMessage:
                    continue 2;
            }
        }
    }

    public function respond(Payload $payload): void
    {
        $this->send($payload->body, $payload->header, $payload->eos);
    }

    public function error(string $error): void
    {
        $frame = new Frame($error, [], Frame::ERROR);

        $this->sendFrame($frame);
    }

    public function stop(): void
    {
        $this->send('', $this->encode(['stop' => true]));
    }

    public function hasPayload(string $class = null): bool
    {
        return $this->findPayload($class) !== null;
    }

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
     */
    private function pullPayload(): ?Payload
    {
        if (!$this->relay->hasFrame()) {
            return null;
        }

        $frame = $this->relay->waitFrame();
        return PayloadFactory::fromFrame($frame);
    }

    private function send(string $body = '', string $header = '', bool $eos = true): void
    {
        $frame = new Frame($header . $body, [\strlen($header)]);

        if (!$eos) {
            $frame->byte10 = Frame::BYTE10_STREAM;
        }

        $this->sendFrame($frame);
    }

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

    private function encode(array $payload): string
    {
        return \json_encode($payload, self::JSON_ENCODE_FLAGS);
    }

    /**
     * Create a new RoadRunner {@see Worker} using global
     * environment ({@see Environment}) configuration.
     */
    public static function create(bool $interceptSideEffects = true): self
    {
        return static::createFromEnvironment(Environment::fromGlobals(), $interceptSideEffects);
    }

    /**
     * Create a new RoadRunner {@see Worker} using passed environment
     * configuration.
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
