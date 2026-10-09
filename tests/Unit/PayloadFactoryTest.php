<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Testo\Test;
use Testo\Assert;
use Testo\Expect;
use Testo\Core\Exception\SkipTest;
use Spiral\Goridge\Frame;
use Spiral\RoadRunner\Exception\RoadRunnerException;
use Spiral\RoadRunner\Message\Command\GetProcessId;
use Spiral\RoadRunner\Message\Command\Pong;
use Spiral\RoadRunner\Message\Command\StreamStop;
use Spiral\RoadRunner\Message\Command\WorkerStop;
use Spiral\RoadRunner\PayloadFactory;

#[Test]
final class PayloadFactoryTest
{
    public function testFromFrameWithStopFlag(): void
    {
        $frame = new Frame("{}", []);
        $frame->byte10 = Frame::BYTE10_STOP;
        $payload = PayloadFactory::fromFrame($frame);

        Assert::instanceOf($payload, StreamStop::class);
    }

    public function testFromFrameWithPongFlag(): void
    {
        $frame = new Frame("{}", []);
        $frame->byte10 = Frame::BYTE10_PONG;
        $payload = PayloadFactory::fromFrame($frame);

        Assert::instanceOf($payload, Pong::class);
    }

    public function testFromFrameWithoutSpecificFlags(): void
    {
        $frame = new Frame("test", [0]);
        $payload = PayloadFactory::fromFrame($frame);

        Assert::notNull($payload);
        Assert::same($payload->body, "test");
        Assert::same($payload->header, "");
    }

    public function testMakeControlWithWorkerStop(): void
    {
        $json = \json_encode(['stop' => true]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        $payload = PayloadFactory::fromFrame($frame);
        Assert::instanceOf($payload, WorkerStop::class);
    }

    public function testMakeControlWithGetProcessId(): void
    {
        $json = \json_encode(['pid' => true]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        $payload = PayloadFactory::fromFrame($frame);
        Assert::instanceOf($payload, GetProcessId::class);
    }

    public function testFromFrameWithControlFlag(): void
    {
        $frame = new Frame(null, [], Frame::CONTROL);

        Expect::exception(RoadRunnerException::class)->withMessageContaining('Invalid task header, JSON payload is expected: Syntax error');
        PayloadFactory::fromFrame($frame);
    }

    public function testMakeControlWithException(): void
    {
        Expect::exception(RoadRunnerException::class)->withMessageContaining('Invalid task header, undefined control package');
        $json = json_encode([]);
        $frame = new Frame($json);
        $frame->setFlag(Frame::CONTROL);

        PayloadFactory::fromFrame($frame);
    }

    public function testMakePayload(): never
    {
        throw new SkipTest('Incomplete: Not implemented yet.');
    }
}