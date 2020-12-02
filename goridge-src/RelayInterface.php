<?php

/**
 * Dead simple, high performance, drop-in bridge to Golang RPC with zero dependencies
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Goridge;

/**
 * Blocking, duplex relay.
 */
interface RelayInterface
{
    /**
     * @return Message|null
     */
    public function waitMessage(): ?Message;

    /**
     * @param Message ...$message
     */
    public function send(Message ...$message): void;
}
