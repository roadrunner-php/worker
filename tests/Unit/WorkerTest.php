<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Mockery;
use Testo\Data\DataProvider;
use Testo\Test;
use Spiral\Goridge\Frame;
use Spiral\Goridge\RelayInterface;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;

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
}
