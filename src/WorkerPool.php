<?php

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\RPCInterface;

final class WorkerPool
{
    private readonly RPCInterface $rpc;

    public function __construct(
        RPCInterface $rpc,
    ) {
        $this->rpc = $rpc->withCodec(new JsonCodec());
    }

    /**
     * Add worker to the pool.
     *
     * @param non-empty-string $plugin
     */
    public function addWorker(string $plugin): void
    {
        $this->rpc->call('informer.AddWorker', $plugin);
    }

    /**
     * Get the worker count for a pool.
     *
     * @param non-empty-string $plugin
     */
    public function countWorkers(string $plugin): int
    {
        return (int)($this->rpc->call('informer.Workers', $plugin)['workers'] ?? 0);
    }

    /**
     * Remove worker from the pool.
     *
     * @param non-empty-string $plugin
     */
    public function removeWorker(string $plugin): void
    {
        $this->rpc->call('informer.RemoveWorker', $plugin);
    }
}
