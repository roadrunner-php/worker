<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Goridge;

final class Message
{
    public const ERROR   = 8;
    public const CONTROL = 16;

    public ?string $body;
    public int     $flags;

    public function __construct(string $body, int $flags = 0)
    {
        $this->body = $body;
        $this->flags = $flags;
    }
}
