<?php

/**
 * This file is part of Info package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Archive;

/**
 * @internal ArchiveInterface is an internal library interface, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 */
interface ArchiveInterface
{
    /**
     * @param string $directory
     * @return mixed
     */
    public function extractInto(string $directory);
}