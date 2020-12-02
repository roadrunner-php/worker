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
    public function waitMessage(): ?Message;

    public function send(Message ...$message): void;
}
