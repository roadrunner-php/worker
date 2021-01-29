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
 * @internal OperatingSystem is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 *
 * @psalm-type OperatingSystemType = OperatingSystem::OS_*
 */
final class OperatingSystem
{
    /**
     * @var string
     */
    public const OS_DARWIN = 'darwin';

    /**
     * @var string
     */
    public const OS_FREEBSD = 'freebsd';

    /**
     * @var string
     */
    public const OS_LINUX = 'linux';

    /**
     * @var string
     */
    public const OS_WINDOWS = 'windows';

    /**
     * @var string
     */
    public const OS_LINUX_ALPINE = 'unknown-musl';

    /**
     * @var string
     */
    private const ERROR_UNKNOWN_OS = 'Current OS (%s) may not be supported';

    /**
     * @return OperatingSystemType
     */
    public static function current(): string
    {
        //
        // PHP_OS_FAMILY === 'Windows', 'BSD', 'Darwin', 'Solaris', 'Linux', 'Unknown'
        //
        switch (\PHP_OS_FAMILY) {
            case 'Windows':
                return self::OS_WINDOWS;

            case 'BSD':
                return self::OS_FREEBSD;

            case 'Darwin':
                return self::OS_DARWIN;

            case 'Linux':
                return self::isAlpine() ? self::OS_LINUX_ALPINE : self::OS_LINUX;

            default:
                throw new \OutOfRangeException(
                    \sprintf(self::ERROR_UNKNOWN_OS, \PHP_OS_FAMILY)
                );
        }
    }

    /**
     * TODO Test this case (not sure if they are correct)
     *
     * @return bool
     */
    private static function isAlpine(): bool
    {
        return \str_contains(\PHP_OS, 'Alpine');
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return \in_array($value, self::all(), true);
    }

    /**
     * @return array<OperatingSystemType>
     */
    public static function all(): array
    {
        static $values;

        if ($values === null) {
            $values = Enum::values(self::class, 'OS_');
        }

        return $values;
    }
}