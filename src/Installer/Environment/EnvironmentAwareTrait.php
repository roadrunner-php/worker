<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Installer\Environment;

trait EnvironmentAwareTrait
{
    /**
     * @param array|null $variables
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function env(?array $variables, string $key, $default)
    {
        return $variables[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}