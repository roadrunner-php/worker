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
 * @internal Architecture is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 *
 * @psalm-type ArchitectureType = Architecture::ARCH_*
 */
final class Architecture
{
    /**
     * @var string
     */
    public const ARCH_X86_64 = 'amd64';

    /**
     * @var string
     */
    private const ERROR_UNKNOWN_ARCH = 'Current architecture (%s) may not be supported';

    /**
     * @var array<string, array<string>>
     */
    private const UNAME_MAPPINGS = [
        self::ARCH_X86_64 => [
            'AMD64',
            'x86',
            'x64',
            'x86_64',
        ],
    ];

    /**
     * @return ArchitectureType
     */
    public static function current(): string
    {
        $uname = \php_uname('m');

        foreach (self::UNAME_MAPPINGS as $result => $available) {
            if (\in_array($uname, $available, true)) {
                return $result;
            }
        }

        throw new \OutOfRangeException(
            \sprintf(self::ERROR_UNKNOWN_ARCH, $uname)
        );
    }

    /**
     * @return array<ArchitectureType>
     */
    public static function all(): array
    {
        static $values;

        if ($values === null) {
            $values = Enum::values(self::class, 'ARCH_');
        }

        return $values;
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return \in_array($value, self::all(), true);
    }
}