<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\WorkerPool;

final class WorkerPoolTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|RPCInterface $rpc;
    private WorkerPool $workerPool;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rpc = $this->createMock(RPCInterface::class);
        $this->rpc->expects($this->once())->method('withCodec')->with($this->isInstanceOf(JsonCodec::class))->willReturnSelf();

        $this->workerPool = new WorkerPool($this->rpc);
    }

    public function testAddWorker(): void
    {
        $this->rpc->expects($this->once())->method('call')->with('informer.AddWorker', 'test');

        $this->workerPool->addWorker('test');
    }

    public function testRemoveWorker(): void
    {
        $this->rpc->expects($this->once())->method('call')->with('informer.RemoveWorker', 'test');

        $this->workerPool->removeWorker('test');
    }
}