<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Testo\Test;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Spiral\Goridge\Frame;
use Spiral\RoadRunner\Message\Command\GetProcessId;
use Spiral\RoadRunner\Tests\Worker\Unit\Stub\TestRelay;
use Spiral\RoadRunner\Worker;

#[Test]
final class StreamResponseTest
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

        Assert::true($worker->hasPayload());
        Assert::true($worker->hasPayload(GetProcessId::class));


        try {
            $worker->waitPayload();
            Assert::fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'There are no frames to return.') {
                throw $e;
            }
        }

        // Worker sends PID to the relay
        Assert::string($this->getRelay()->getReceivedBody())->matchesRegex('/\{\"pid\":\\d++}/');
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

        Assert::true($worker->hasPayload());
        Assert::true($worker->hasPayload(GetProcessId::class));
        // After STOP command worker should not wait for payload and return null
        Assert::null($worker->waitPayload());
        // Worker sends PID to the relay
        Assert::string($this->getRelay()->getReceivedBody())->matchesRegex('/\{\"pid\":\\d++}/');
    }

    #[AfterTest]
    protected function tearDown(): void
    {
        unset($this->relay, $this->worker);
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