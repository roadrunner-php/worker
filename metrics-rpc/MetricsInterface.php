<?php

/**
 * High-performance PHP process supervisor and load balancer written in Go.
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Metrics;

use Spiral\RoadRunner\Exception\MetricException;

interface MetricsInterface
{
    /**
     * Add collector value. Fallback to appropriate method of related collector.
     *
     * @param string  $collector
     * @param float   $value
     * @param mixed[] $labels
     *
     * @throws MetricException
     * @return void
     */
    public function add(string $collector, float $value, array $labels = []);

    /**
     * Subtract the collector value, only for gauge collector.
     *
     * @param string  $collector
     * @param float   $value
     * @param mixed[] $labels
     *
     * @throws MetricException
     * @return void
     */
    public function sub(string $collector, float $value, array $labels = []);

    /**
     * Observe collector value, only for histogram and summary collectors.
     *
     * @param string  $collector
     * @param float   $value
     * @param mixed[] $labels
     *
     * @throws MetricException
     * @return void
     */
    public function observe(string $collector, float $value, array $labels = []);

    /**
     * Set collector value, only for gauge collector.
     *
     * @param string  $collector
     * @param float   $value
     * @param mixed[] $labels
     *
     * @throws MetricException
     * @return void
     */
    public function set(string $collector, float $value, array $labels = []);
}
