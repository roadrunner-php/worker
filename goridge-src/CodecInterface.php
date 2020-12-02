<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Goridge;

interface CodecInterface
{
    public function getIndex(): int;

    public function encode($payload): string;

    public function decode(string $payload);
}
