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
 * @internal ZipArchive is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 */
final class ZipArchive extends Archive
{
    public function extractInto(string $directory)
    {
        throw new \LogicException(__METHOD__ . ' not implemented yet');
    }
}