<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Mockery;
use Testo\Data\DataProvider;
use Mockery\MockInterface;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Testo\Assert;
use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Informer\Worker;
use Spiral\RoadRunner\Informer\Workers;
use Spiral\RoadRunner\WorkerPool;

#[Test]
final class WorkerPoolTest
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

    private MockInterface|RPCInterface $rpc;
    private WorkerPool $workerPool;

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->rpc = Mockery::mock(RPCInterface::class)->shouldIgnoreMissing();
        $this->rpc->shouldReceive('withCodec')->once()->with(Mockery::type(JsonCodec::class), Mockery::andAnyOtherArgs())->andReturnSelf();

        $this->workerPool = new WorkerPool($this->rpc);
    }

    public function testAddWorker(): void
    {
        $this->rpc->shouldReceive('call')->once()->with('informer.AddWorker', 'test', Mockery::andAnyOtherArgs());

        $this->workerPool->addWorker('test');
    }

    #[DataProvider('countDataProvider')]
    public function testCountWorkers(int $expected, array $workers): void
    {
        $this->rpc->shouldReceive('call')->once()->with('informer.Workers', 'test', Mockery::andAnyOtherArgs())->andReturn(['workers' => $workers]);

        Assert::same($this->workerPool->countWorkers('test'), $expected);
    }

    #[DataProvider('getWorkersDataProvider')]
    public function testGetWorkers(array $expected, array $workers): void
    {
        $this->rpc->shouldReceive('call')->once()->with('informer.Workers', 'test', Mockery::andAnyOtherArgs())->andReturn(['workers' => $workers]);

        Assert::equals($this->workerPool->getWorkers('test'), new Workers($expected));
    }

    public function testRemoveWorker(): void
    {
        $this->rpc->shouldReceive('call')->once()->with('informer.RemoveWorker', 'test', Mockery::andAnyOtherArgs());

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
