<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\Goridge\Frame;
use Spiral\RoadRunner\Message\Command\GetProcessId;
use Spiral\RoadRunner\Tests\Worker\Unit\Stub\TestRelay;
use Spiral\RoadRunner\Worker;

class StreamResponseTest extends TestCase
{
    private TestRelay $relay;
    private Worker $worker;

    /**
     * Server requests worker's PID
     */
    public function testGetPid(): void
    {
        $worker = $this->getWorker();
        $this->getRelay()
            ->addFrames(
                new Frame('{"pid":true}', [], Frame::CONTROL),
            );

        self::assertTrue($worker->hasPayload());
        self::assertTrue($worker->hasPayload(GetProcessId::class));


        try {
            $worker->waitPayload();
            self::fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'There are no frames to return.') {
                throw $e;
            }
        }

        // Worker sends PID to the relay
        self::assertMatchesRegularExpression('/\{\"pid\":\\d++}/', $this->getRelay()->getReceivedBody());
    }

    /**
     * Worker sends WORKER STOP command
     */
    public function testStopCommand(): void
    {
        $worker = $this->getWorker();
        $this->getRelay()
            ->addFrames(
                new Frame('{"pid":true}', [], Frame::CONTROL),
                new Frame('{"stop":true}', [], Frame::CONTROL),
            );

        self::assertTrue($worker->hasPayload());
        self::assertTrue($worker->hasPayload(GetProcessId::class));
        // After STOP command worker should not wait for payload and return null
        self::assertNull($worker->waitPayload());
        // Worker sends PID to the relay
        self::assertMatchesRegularExpression('/\{\"pid\":\\d++}/', $this->getRelay()->getReceivedBody());
    }

    protected function tearDown(): void
    {
        unset($this->relay, $this->worker);
        parent::tearDown();
    }

    private function getRelay(): TestRelay
    {
        return $this->relay ??= new TestRelay();
    }

    private function getWorker(): Worker
    {
        return $this->worker ??= new Worker(
            relay: $this->getRelay(),
            interceptSideEffects: false
        );
    }
}