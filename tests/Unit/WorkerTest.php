<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spiral\Goridge\Frame;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;

final class WorkerTest extends TestCase
{
    #[DataProvider('respondDataProvider')]
    public function testRespond(int $expectedFlags, ?int $codec): void
    {
        $expected = new Frame('Hello World!', [0 => 0], $expectedFlags);

        $relay = $this->createMock(RelayInterface::class);
        $relay
            ->expects($this->once())
            ->method('send')
            ->with($this->equalTo($expected));

        $worker = new Worker($relay, false);

        $worker->respond(new Payload('Hello World!'), $codec);
    }

    public static function respondDataProvider(): \Traversable
    {
        yield [0, null];
        yield [Frame::CODEC_PROTO, Frame::CODEC_PROTO];
        yield [Frame::CODEC_JSON, Frame::CODEC_JSON];
    }
}
