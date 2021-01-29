<?php

/**
 * This file is part of Info package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Binary;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * @internal Info is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 *
 * @psalm-import-type ArchitectureType from Architecture
 * @psalm-import-type OperatingSystemType from OperatingSystem
 */
final class Info
{
    /**
     * @var string
     */
    private string $version;

    /**
     * @var string
     */
    private $os;

    /**
     * @var string
     */
    private $arch;

    /**
     * @param string $version
     * @param OperatingSystemType $os
     * @param ArchitectureType $arch
     */
    public function __construct(
        string $version,
        #[ExpectedValues(valuesFromClass: OperatingSystem::class)]
        string $os,
        #[ExpectedValues(valuesFromClass: Architecture::class)]
        string $arch
    ) {
        $this->version = $version;

        assert(OperatingSystem::isValid($os), 'Invalid $os argument value: ' . $os);
        $this->os = $os;

        assert(Architecture::isValid($arch), 'Invalid $arch argument value: ' . $arch);
        $this->arch = $arch;
    }
}