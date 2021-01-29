<?php

/**
 * This file is part of Info package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Binary;

/**
 * @internal Enum is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 */
final class Enum
{
    /**
     * @param class-string $class
     * @param string $prefix
     * @return array<string, string|int>
     */
    public static function values(string $class, string $prefix): array
    {
        $result = [];

        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return [];
        }

        foreach ($reflection->getConstants() as $name => $value) {
            if (\str_starts_with($name, $prefix)) {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}