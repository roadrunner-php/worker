<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Composer\InstalledVersions;

final class Version
{
    /**
     * @var string
     */
    public const PACKAGE_NAME = 'spiral/roadrunner-worker';

    /**
     * @var string
     */
    public const VERSION_FALLBACK = 'dev-master';

    /**
     * @return string
     */
    public static function current(): string
    {
        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME) ?: self::VERSION_FALLBACK;
    }
}