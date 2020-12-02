<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Goridge\RPC;

interface RPCInterface
{
    /**
     * Invoke remove RoadRunner service method using given payload (free form).
     *
     * @param string $method
     * @param mixed  $payload
     * @return mixed
     */
    public function call(string $method, $payload);
}
