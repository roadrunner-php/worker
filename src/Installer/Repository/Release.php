<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Installer\Repository;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use JetBrains\PhpStorm\ExpectedValues;

abstract class Release implements ReleaseInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    #[ExpectedValues(valuesFromClass: Stability::class)]
    private string $stability;

    /**
     * @var string
     */
    private string $version;

    /**
     * @var AssetsCollection
     */
    private AssetsCollection $assets;

    /**
     * @param string $name
     * @param iterable $assets
     */
    public function __construct(string $name, iterable $assets = [])
    {
        $this->name = $name;
        $this->assets = AssetsCollection::create($assets);

        $this->version = $this->parseVersion($name);
        $this->stability = $this->parseStability($name);
    }

    /**
     * @param string $name
     * @return string
     */
    private function parseStability(string $name): string
    {
        return VersionParser::parseStability($name);
    }

    /**
     * @param string $name
     * @return string
     */
    private function parseVersion(string $name): string
    {
        $version = (new VersionParser())->normalize($name);

        $parts = \explode('-', $version);
        $number = \substr($parts[0], 0, -2);

        return isset($parts[1])
            ? $number . '-' . $parts[1]
            : $number
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * {@inheritDoc}
     */
    #[ExpectedValues(valuesFromClass: Stability::class)]
    public function getStability(): string
    {
        return $this->stability;
    }

    /**
     * {@inheritDoc}
     */
    public function getAssets(): AssetsCollection
    {
        return $this->assets;
    }

    /**
     * {@inheritDoc}
     */
    public function satisfies(string $constraint): bool
    {
        return Semver::satisfies($this->getVersion(), $constraint);
    }
}