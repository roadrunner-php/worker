<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Mockery;
use Psr\Log\LoggerInterface;
use Spiral\Goridge\Exception\GoridgeException;
use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Frame;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Exception\RoadRunnerException;
use Spiral\RoadRunner\Logger;
use Spiral\RoadRunner\Message\Command\StreamStop;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Tests\Worker\Unit\Stub\BlockingTestRelay;
use Spiral\RoadRunner\Tests\Worker\Unit\Stub\TestRelay;
use Spiral\RoadRunner\Worker;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class WorkerTest
{
    #[DataProvider('respondDataProvider')]
    public function testRespond(int $expectedFlags, ?int $codec): void
    {
        $expected = new Frame('Hello World!', [0 => 0], $expectedFlags);

        $relay = Mockery::mock(RelayInterface::class)->shouldIgnoreMissing();
        $relay->shouldReceive('send')->once()->with(Mockery::isEqual($expected), Mockery::andAnyOtherArgs());

        $worker = new Worker($relay, false);

        $worker->respond(new Payload('Hello World!'), $codec);
    }

    public static function respondDataProvider(): \Traversable
    {
        yield [0, null];
        yield [Frame::CODEC_PROTO, Frame::CODEC_PROTO];
        yield [Frame::CODEC_JSON, Frame::CODEC_JSON];
    }

    public function testRespondPrependsHeaderToBody(): void
    {
        $relay = Mockery::mock(RelayInterface::class);
        $relay->expects('send')->with(Mockery::isEqual(new Frame('headerbody', [6])));

        $worker = new Worker($relay, false);

        $worker->respond(new Payload('body', 'header'));
    }

    public function testRespondWithStreamChunkSetsStreamFlag(): void
    {
        $relay = new TestRelay();
        $worker = new Worker($relay, false);

        $worker->respond(new Payload('chunk', null, false));
        $worker->respond(new Payload('last'));

        [$chunk, $last] = $relay->getReceived();
        Assert::same($chunk->byte10 & Frame::BYTE10_STREAM, Frame::BYTE10_STREAM);
        Assert::same($last->byte10 & Frame::BYTE10_STREAM, 0);
    }

    public function testErrorSendsErrorFrame(): void
    {
        $relay = Mockery::mock(RelayInterface::class);
        $relay->expects('send')->with(Mockery::isEqual(new Frame('Something went wrong', [], Frame::ERROR)));

        $worker = new Worker($relay, false);

        $worker->error('Something went wrong');
    }

    public function testStopSendsStopHeader(): void
    {
        $relay = Mockery::mock(RelayInterface::class);
        $relay->expects('send')->with(Mockery::isEqual(new Frame('{"stop":true}', [13])));

        $worker = new Worker($relay, false);

        $worker->stop();
    }

    public function testRelayGoridgeExceptionIsRethrownAsTransportException(): never
    {
        $previous = new GoridgeException('Connection lost', 42);
        $relay = Mockery::mock(RelayInterface::class);
        $relay->allows('send')->andThrow($previous);
        $worker = new Worker($relay, false);

        Expect::exception(TransportException::class)
            ->withMessage('Connection lost')
            ->withCode(42)
            ->withPrevious($previous);

        $worker->respond(new Payload('body'));
    }

    public function testRelayErrorIsRethrownAsRoadRunnerException(): never
    {
        $previous = new \LogicException('Unexpected failure', 7);
        $relay = Mockery::mock(RelayInterface::class);
        $relay->allows('send')->andThrow($previous);
        $worker = new Worker($relay, false);

        Expect::exception(RoadRunnerException::class)
            ->withMessage('Unexpected failure')
            ->withCode(7)
            ->withPrevious($previous);

        $worker->error('error');
    }

    public function testWaitPayloadReturnsPayload(): void
    {
        $relay = (new TestRelay())->addFrames(new Frame('headerbody', [6]));
        $worker = new Worker($relay, false);

        $payload = $worker->waitPayload();

        Assert::same($payload::class, Payload::class);
        Assert::same($payload->header, 'header');
        Assert::same($payload->body, 'body');
    }

    public function testWaitPayloadSkipsPongAndStreamStop(): void
    {
        $relay = (new TestRelay())->addFrames(
            self::pongFrame(),
            self::streamStopFrame(),
            new Frame('body', [0]),
        );
        $worker = new Worker($relay, false);

        $payload = $worker->waitPayload();

        Assert::same($payload->body, 'body');
        Assert::same($relay->getReceived(), []);
    }

    public function testWaitPayloadReturnsBufferedPayloadsInOrder(): void
    {
        $relay = (new TestRelay())->addFrames(new Frame('first', [0]), new Frame('second', [0]));
        $worker = new Worker($relay, false);

        Assert::false($worker->hasPayload(StreamStop::class));

        Assert::same($worker->waitPayload()->body, 'first');
        Assert::same($worker->waitPayload()->body, 'second');
    }

    public function testGetPayloadReturnsNullWithoutFrames(): void
    {
        $worker = new Worker(new TestRelay(), false);

        Assert::false($worker->hasPayload());
        Assert::null($worker->getPayload());
    }

    public function testGetPayloadRemovesPayloadFromBuffer(): void
    {
        $relay = (new TestRelay())->addFrames(new Frame('body', [0]));
        $worker = new Worker($relay, false);

        $payload = $worker->getPayload();

        Assert::same($payload->body, 'body');
        Assert::false($worker->hasPayload());
        Assert::null($worker->getPayload());
    }

    public function testGetPayloadByClassKeepsOtherPayloadsBuffered(): void
    {
        $relay = (new TestRelay())->addFrames(new Frame('regular', [0]), self::streamStopFrame());
        $worker = new Worker($relay, false);

        Assert::true($worker->hasPayload(StreamStop::class));
        $stop = $worker->getPayload(StreamStop::class);

        Assert::instanceOf($stop, StreamStop::class);
        Assert::false($worker->hasPayload(StreamStop::class));
        Assert::same($worker->getPayload()->body, 'regular');
        Assert::false($worker->hasPayload());
    }

    public function testHasPayloadConsumesPong(): void
    {
        $relay = (new TestRelay())->addFrames(self::pongFrame());
        $worker = new Worker($relay, false);

        Assert::false($worker->hasPayload());
        Assert::false($relay->hasFrame());
    }

    public function testHasPayloadDoesNotReadBlockingRelayOutsideStreamMode(): void
    {
        $relay = (new BlockingTestRelay())->addFrames(new Frame('body', [0]));
        $worker = new Worker($relay, false);

        Assert::false($worker->hasPayload());
        Assert::true($relay->hasFrame());
    }

    public function testStreamModePingsBlockingRelayEveryFiveFrames(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();

        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        Assert::false($worker->hasPayload());
        $worker->respond(new Payload('chunk', null, false));

        $received = $relay->getReceived();
        Assert::count($received, 6);
        foreach (\array_slice($received, 0, 5) as $frame) {
            Assert::same($frame->byte10 & Frame::BYTE10_PING, 0);
        }
        Assert::same($received[5]->byte10 & Frame::BYTE10_PING, Frame::BYTE10_PING);
    }

    public function testStreamModeDoesNotPingBeforeFirstFrame(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();

        Assert::false($worker->hasPayload());
        $worker->respond(new Payload('chunk', null, false));

        Assert::same($relay->getReceived()[0]->byte10 & Frame::BYTE10_PING, 0);
    }

    public function testStreamModeStopsPingingAfterPong(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();
        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('ping', null, false));
        $relay->addFrames(self::pongFrame());

        Assert::false($worker->hasPayload());
        $worker->respond(new Payload('after pong', null, false));

        Assert::false($relay->hasFrame());
        $received = $relay->getReceived();
        Assert::same(\end($received)->byte10 & Frame::BYTE10_PING, 0);
    }

    public function testStreamModeResumesPingingAfterPong(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();
        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('ping', null, false));
        $relay->addFrames(self::pongFrame());
        $worker->hasPayload();

        for ($i = 0; $i < 4; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('second ping', null, false));

        $received = $relay->getReceived();
        Assert::count($received, 11);
        Assert::same($received[10]->byte10 & Frame::BYTE10_PING, Frame::BYTE10_PING);
    }

    public function testWaitPayloadAcceptsPongInStreamMode(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();
        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('ping', null, false));
        $relay->addFrames(self::pongFrame(), new Frame('next', [0]));

        Assert::same($worker->waitPayload()->body, 'next');

        for ($i = 0; $i < 4; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('second ping', null, false));

        $received = $relay->getReceived();
        Assert::same(\end($received)->byte10 & Frame::BYTE10_PING, Frame::BYTE10_PING);
    }

    public function testStreamModeReadsFramesWhileWaitingForPong(): void
    {
        $relay = new BlockingTestRelay();
        $worker = (new Worker($relay, false))->withStreamMode();
        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('ping', null, false));
        $relay->addFrames(self::streamStopFrame());

        Assert::true($worker->hasPayload(StreamStop::class));
    }

    public function testWithStreamModeDoesNotChangeOriginalWorker(): void
    {
        $relay = new BlockingTestRelay();
        $worker = new Worker($relay, false);

        $streamWorker = $worker->withStreamMode();
        for ($i = 0; $i < 5; ++$i) {
            $worker->respond(new Payload('chunk', null, false));
        }
        $worker->hasPayload();
        $worker->respond(new Payload('chunk', null, false));

        Assert::notSame($streamWorker, $worker);
        foreach ($relay->getReceived() as $frame) {
            Assert::same($frame->byte10 & Frame::BYTE10_PING, 0);
        }
    }

    public function testDefaultLogger(): void
    {
        $worker = new Worker(new TestRelay(), false);

        Assert::instanceOf($worker->getLogger(), Logger::class);
    }

    public function testCreateFromEnvironment(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);

        $worker = Worker::createFromEnvironment(new Environment(['RR_RELAY' => 'pipes']), false, $logger);

        Assert::instanceOf($worker, Worker::class);
        Assert::same($worker->getLogger(), $logger);
    }

    public function testCreateUsesGlobalEnvironment(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $backup = [$_ENV, $_SERVER];
        $_SERVER['RR_RELAY'] = 'pipes';

        try {
            $worker = Worker::create(false, $logger);
        } finally {
            [$_ENV, $_SERVER] = $backup;
        }

        Assert::same($worker->getLogger(), $logger);
    }

    public function testInterceptSideEffectsRedirectsOutputBuffer(): void
    {
        $level = \ob_get_level();

        new Worker(new TestRelay(), true);

        try {
            Assert::same(\ob_get_level(), $level + 1);
        } finally {
            while (\ob_get_level() > $level) {
                \ob_end_clean();
            }
        }
    }

    private static function pongFrame(): Frame
    {
        $frame = new Frame(null, [0]);
        $frame->byte10 = Frame::BYTE10_PONG;
        return $frame;
    }

    private static function streamStopFrame(): Frame
    {
        $frame = new Frame(null, [0]);
        $frame->byte10 = Frame::BYTE10_STOP;
        return $frame;
    }
}
