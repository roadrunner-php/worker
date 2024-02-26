<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit\Informer;

use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Informer\Worker;
use Spiral\RoadRunner\Informer\Workers;

final class WorkersTest extends TestCase
{
    public function testGetWorkers(): void
    {
        $workers = [
            new Worker(1, 1, 1, 1, 1, 1.0, 'test1', 'test1'),
            new Worker(2, 2, 2, 2, 2, 2.0, 'test2', 'test2'),
        ];

        $this->assertEquals([], (new Workers())->getWorkers());
        $this->assertEquals($workers, (new Workers($workers))->getWorkers());
    }
}
