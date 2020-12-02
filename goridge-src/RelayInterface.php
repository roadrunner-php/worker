<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Goridge;

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
