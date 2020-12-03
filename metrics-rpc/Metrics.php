<?php

/**
 * High-performance PHP process supervisor and load balancer written in Go.
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Metrics;

use Spiral\Goridge\RPC;
use Spiral\RoadRunner\Metrics\Exception\MetricsException;

/**
 * Application metrics.
 */
final class Metrics implements MetricsInterface
{
    /** @var RPC\RPCInterface */
    private RPC\RPCInterface $rpc;

    /**
     * @param RPC\RPCInterface $rpc
     */
    public function __construct(RPC\RPCInterface $rpc)
    {
        $this->rpc = $rpc
            ->withServicePrefix('metrics')
            ->withCodec(new RPC\Codec\JsonCodec());
    }

    /**
     * @inheritDoc
     */
    public function add(string $name, float $value, array $labels = []): void
    {
        try {
            $this->rpc->call('add', compact('name', 'value', 'labels'));
        } catch (RPC\Exception\RPCException $e) {
            throw new MetricsException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function sub(string $name, float $value, array $labels = []): void
    {
        try {
            $this->rpc->call('sub', compact('name', 'value', 'labels'));
        } catch (RPC\Exception\RPCException $e) {
            throw new MetricsException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function observe(string $name, float $value, array $labels = []): void
    {
        try {
            $this->rpc->call('observe', compact('name', 'value', 'labels'));
        } catch (RPC\Exception\RPCException $e) {
            throw new MetricsException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, float $value, array $labels = []): void
    {
        try {
            $this->rpc->call('set', compact('name', 'value', 'labels'));
        } catch (RPC\Exception\RPCException $e) {
            throw new MetricsException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
