<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\Goridge\Frame;
use Spiral\RoadRunner\Exception\RoadRunnerException;
use Spiral\RoadRunner\Message\Command\GetProcessId;
use Spiral\RoadRunner\Message\Command\Pong;
use Spiral\RoadRunner\Message\Command\StreamStop;
use Spiral\RoadRunner\Message\Command\WorkerStop;
use Spiral\RoadRunner\PayloadFactory;

final class PayloadFactoryTest extends TestCase
{
    public function testFromFrameWithStopFlag(): void
    {
        $frame = new Frame("{}", []);
        $frame->byte10 = Frame::BYTE10_STOP;
        $payload = PayloadFactory::fromFrame($frame);

        $this->assertInstanceOf(StreamStop::class, $payload);
    }

    public function testFromFrameWithPongFlag(): void
    {
        $frame = new Frame("{}", []);
        $frame->byte10 = Frame::BYTE10_PONG;
        $payload = PayloadFactory::fromFrame($frame);

        $this->assertInstanceOf(Pong::class, $payload);
    }

    public function testFromFrameWithoutSpecificFlags(): void
    {
        $frame = new Frame("test", [0]);
        $payload = PayloadFactory::fromFrame($frame);

        $this->assertNotNull($payload);
        $this->assertSame("test", $payload->body);
        $this->assertSame("", $payload->header);
    }

    public function testMakeControlWithWorkerStop(): void
    {
        $json = \json_encode(['stop' => true]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        $payload = PayloadFactory::fromFrame($frame);
        $this->assertInstanceOf(WorkerStop::class, $payload);
    }

    public function testMakeControlWithGetProcessId(): void
    {
        $json = \json_encode(['pid' => true]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        $payload = PayloadFactory::fromFrame($frame);
        $this->assertInstanceOf(GetProcessId::class, $payload);
    }

    public function testFromFrameWithControlFlag(): void
    {
        $frame = new Frame(null, [], Frame::CONTROL);

        $this->expectException(RoadRunnerException::class);
        $this->expectExceptionMessage('Invalid task header, JSON payload is expected: Syntax error');
        PayloadFactory::fromFrame($frame);
    }

    public function testMakeControlWithException(): void
    {
        $this->expectException(RoadRunnerException::class);
        $this->expectExceptionMessage('Invalid task header, undefined control package');
        $json = json_encode([]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        PayloadFactory::fromFrame($frame);
    }

    public function testMakePayload(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}