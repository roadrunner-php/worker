<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Client;

use Composer\Semver\CompilingMatcher;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

/**
 * @internal VersionsCollection is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\RoadRunner\Console
 */
final class VersionsCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, Version>
     */
    private array $versions;

    /**
     * @param array $versions
     */
    public function __construct(array $versions)
    {
        $this->versions = $versions;
    }

    /**
     * @param string $expr
     * @return $this
     */
    public function matched(string $expr): self
    {
        return $this->filter(function (Version $version) use ($expr): bool {
            return Semver::satisfies($version->version, $expr);
        });
    }

    /**
     * @return $this
     */
    public function withAssets(): self
    {
        return $this->filter(static fn(Version $version): bool => $version->assets !== []);
    }

    /**
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter): self
    {
        return new self(\array_filter($this->versions, $filter));
    }

    /**
     * @param bool $asc
     * @return $this
     */
    public function sortByVersion(bool $asc = false): self
    {
        $result = $this->versions;

        $asc ? \ksort($result, \SORT_NATURAL) : \krsort($result, \SORT_NATURAL);

        return new self($result);
    }

    /**
     * @param string $stability
     * @return $this
     */
    public function stability(string $stability): self
    {
        return $this->filter(static fn(Version $version): bool => $version->stability === $stability);
    }

    /**
     * @return $this
     */
    public function stable(): self
    {
        return $this->stability('stable');
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->versions);
    }

    /**
     * @return bool
     */
    public function empty(): bool
    {
        return $this->versions === [];
    }

    /**
     * @return \Traversable<Version>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(\array_values($this->versions));
    }

    /**
     * @return Version|null
     */
    public function first(): ?Version
    {
        return $this->count() > 0 ? \reset($this->versions) : null;
    }
}