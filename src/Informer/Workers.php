<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Informer;

final class Workers implements \Countable
{
    /**
     * @param array<Worker> $workers
     */
    public function __construct(
        public array $workers = [],
    ) {
    }

    public function count(): int
    {
        return \count($this->workers);
    }
}
