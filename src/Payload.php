<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class Payload
{
    /**
     * Execution payload (binary).
     *
     * @psalm-readonly
     * @var string
     */
    public string $body = '';

    /**
     * Execution context (binary).
     *
     * @psalm-readonly
     */
    public string $header = '';

    /**
     * @psalm-readonly
     */
    public bool $chunked = false;

    public function __construct(?string $body, ?string $header = null, bool $chunked = false)
    {
        $this->body = $body ?? '';
        $this->header = $header ?? '';
        $this->chunked = $chunked;
    }
}
