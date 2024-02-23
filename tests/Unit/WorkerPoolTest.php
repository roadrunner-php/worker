<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Informer\Worker;
use Spiral\RoadRunner\Informer\Workers;
use Spiral\RoadRunner\WorkerPool;

final class WorkerPoolTest extends TestCase
{
    private const EXAMPLE_WORKER = [
        'pid' => 1,
        'status' => 1,
        'numExecs' => 1,
        'created' => 1,
        'memoryUsage' => 1,
        'CPUPercent' => 1.0,
        'command' => 'test',
        'statusStr' => 'test',
    ];

    private \PHPUnit\Framework\MockObject\MockObject|RPCInterface $rpc;
    private WorkerPool $workerPool;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->rpc = $this->createMock(RPCInterface::class);
        $this->rpc
            ->expects($this->once())
            ->method('withCodec')
            ->with($this->isInstanceOf(JsonCodec::class))
            ->willReturnSelf();

        $this->workerPool = new WorkerPool($this->rpc);
    }

    public function testAddWorker(): void
    {
        $this->rpc->expects($this->once())->method('call')->with('informer.AddWorker', 'test');

        $this->workerPool->addWorker('test');
    }

    #[DataProvider('countDataProvider')]
    public function testCountWorkers(int $expected, array $workers): void
    {
        $this->rpc
            ->expects($this->once())
            ->method('call')
            ->with('informer.Workers', 'test')
            ->willReturn(['workers' => $workers]);

        $this->assertSame($expected, $this->workerPool->countWorkers('test'));
    }

    #[DataProvider('getWorkersDataProvider')]
    public function testGetWorkers(array $expected, array $workers): void
    {
        $this->rpc
            ->expects($this->once())
            ->method('call')
            ->with('informer.Workers', 'test')
            ->willReturn(['workers' => $workers]);

        $this->assertEquals(new Workers($expected), $this->workerPool->getWorkers('test'));
    }

    public function testRemoveWorker(): void
    {
        $this->rpc->expects($this->once())->method('call')->with('informer.RemoveWorker', 'test');

        $this->workerPool->removeWorker('test');
    }

    public static function countDataProvider(): \Traversable
    {
        yield [0, []];
        yield [2, [self::EXAMPLE_WORKER, self::EXAMPLE_WORKER]];
    }

    public static function getWorkersDataProvider(): \Traversable
    {
        yield [[], []];

        $workers = \array_map(static function (array $worker): Worker {
            return new Worker(
                pid: $worker['pid'],
                statusCode: $worker['status'],
                executions: $worker['numExecs'],
                createdAt:  $worker['created'],
                memoryUsage: $worker['memoryUsage'],
                cpuUsage: $worker['CPUPercent'],
                command: $worker['command'],
                status: $worker['statusStr'],
            );
        }, [
            self::EXAMPLE_WORKER,
            self::EXAMPLE_WORKER,
        ]);

        yield [$workers, [self::EXAMPLE_WORKER, self::EXAMPLE_WORKER]];
    }
}
