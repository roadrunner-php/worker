<?php

/**
 * This file is part of Info package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Archive;

use JetBrains\PhpStorm\Immutable;

/**
 * @internal Archive is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 */
abstract class Archive implements ArchiveInterface
{
    /**
     * @var string
     */
    #[Immutable]
    protected string $archive;

    /**
     * @param string $archive
     */
    public function __construct(string $archive)
    {
        $this->assertArchiveValid($archive);

        $this->archive = $archive;
    }

    /**
     * @param string $archive
     */
    private function assertArchiveValid(string $archive): void
    {
        if (! \is_file($archive) || ! \is_readable($archive)) {
            throw new \InvalidArgumentException(
                \sprintf('Archive file "%s" is not readable', $archive)
            );
        }
    }
}