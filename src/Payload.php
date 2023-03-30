<?php

declare(strict_types=1);

namespace Spiral\RoadRunner;

use JetBrains\PhpStorm\Immutable;

/**
 * @internal
 */
#[Immutable]
class Payload
{
    /**
     * Execution payload (binary).
     *
     * @psalm-readonly
     * @var string
     */
    public readonly string $body;

    /**
     * Execution context (binary).
     *
     * @psalm-readonly
     */
    public readonly string $header;

    /**
     * End of stream.
     * The {@see true} value means the Payload block is last in the stream.
     *
     * @psalm-readonly
     */
    public readonly bool $eos;

    public function __construct(?string $body, ?string $header = null, bool $eos = true)
    {
        $this->body = $body ?? '';
        $this->header = $header ?? '';
        $this->eos = $eos;
    }
}
