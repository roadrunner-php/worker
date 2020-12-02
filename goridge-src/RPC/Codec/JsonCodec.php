<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Goridge\RPC\Codec;

use Spiral\Goridge\RPC\CodecInterface;

final class JsonCodec implements CodecInterface
{
    /**
     * Coded index, uniquely identified by remote server.
     *
     * @return int
     */
    public function getIndex(): int
    {
        return 1;
    }

    /**
     * @param mixed $payload
     * @return string
     */
    public function encode($payload): string
    {
        return json_encode($payload);
    }

    /**
     * @param string $payload
     * @return mixed
     */
    public function decode(string $payload)
    {
        return json_decode($payload, true);
    }
}
