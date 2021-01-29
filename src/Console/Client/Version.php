<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Client;

use Composer\Semver\VersionParser;
use JetBrains\PhpStorm\Immutable;
use Spiral\RoadRunner\Console\Binary\Architecture;
use Spiral\RoadRunner\Console\Binary\OperatingSystem;

#[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
abstract class Version
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $stability;

    /**
     * @var string
     */
    public string $version;

    /**
     * @var array<Asset>
     */
    public array $assets = [];

    /**
     * @var \DateTimeInterface
     */
    public \DateTimeInterface $created;

    /**
     * @param string $name
     * @param array<Asset> $assets
     */
    public function __construct(string $name, array $assets = [])
    {
        $this->name = $name;
        $this->stability = VersionParser::parseStability($name);
        $this->version = $this->parseVersionNumber($name);
        $this->assets = $assets;

        $this->touch();
    }

    /**
     * @param string $os
     * @param string $arch
     * @return Asset|null
     */
    abstract public function asset(string $os, string $arch): ?Asset;

    /**
     * @param \DateTimeInterface|null $date
     */
    public function touch(\DateTimeInterface $date = null): void
    {
        $this->created = $date ?? new \DateTimeImmutable();
    }

    /**
     * @param string $version
     * @return string
     */
    private function parseVersionNumber(string $version): string
    {
        $version = (new VersionParser())->normalize($version);

        $parts = \explode('-', $version);
        $number = \substr($parts[0], 0, -2);

        return isset($parts[1])
            ? $number . '-' . $parts[1]
            : $number
        ;
    }

    /**
     * @return bool
     */
    public function hasAssets(): bool
    {
        return $this->assets !== [];
    }
}