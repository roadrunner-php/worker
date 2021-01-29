<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Client;

final class GitHubVersion extends Version
{
    /**
     * @param string $os
     * @param string $arch
     * @return Asset|null
     */
    public function asset(string $os, string $arch): ?Asset
    {
        foreach ($this->assets as $asset) {
            if (\str_contains($asset->name, $os . '-' . $arch)) {
                return $asset;
            }
        }

        return null;
    }
}