<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit\Informer;

use Testo\Test;
use Testo\Assert;
use Spiral\RoadRunner\Informer\Worker;
use Spiral\RoadRunner\Informer\Workers;

#[Test]
final class WorkersTest
{
    public function testGetWorkers(): void
    {
        $workers = [
            new Worker(1, 1, 1, 1, 1, 1.0, 'test1', 'test1'),
            new Worker(2, 2, 2, 2, 2, 2.0, 'test2', 'test2'),
        ];

        Assert::equals((new Workers())->getWorkers(), []);
        Assert::equals((new Workers($workers))->getWorkers(), $workers);
    }
}
