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

/**
 * @template-extends Collection<ReleaseInterface>
 */
final class ReleasesCollection extends Collection
{
    /**
     * @param string ...$constraints
     * @return $this
     */
    public function satisfies(string ...$constraints): self
    {
        $result = $this;

        foreach ($this->constraints($constraints) as $constraint) {
            $result = $result->filter(static fn (ReleaseInterface $r): bool => $r->satisfies($constraint));
        }

        return $result;
    }

    /**
     * @param string ...$constraints
     * @return $this
     */
    public function notSatisfies(string ...$constraints): self
    {
        $result = $this;

        foreach ($this->constraints($constraints) as $constraint) {
            $result = $result->except(static fn (ReleaseInterface $r): bool => $r->satisfies($constraint));
        }

        return $result;
    }

    /**
     * @param array<string> $constraints
     * @return array<string>
     */
    private function constraints(array $constraints): array
    {
        $result = [];

        foreach ($constraints as $constraint) {
            foreach (\explode('|', $constraint) as $expression) {
                $result[] = $expression;
            }
        }

        return \array_unique(
            \array_filter(
                \array_map('\\trim', $result)
            )
        );
    }

    /**
     * @return $this
     */
    public function withAssets(): self
    {
        return $this->filter(static fn(ReleaseInterface $r): bool =>
            ! $r->getAssets()->empty()
        );
    }

    /**
     * @param bool $asc
     * @return $this
     */
    public function sortByVersion(bool $asc = false): self
    {
        $result = $this->items;

        $asc ? \ksort($result, \SORT_NATURAL) : \krsort($result, \SORT_NATURAL);

        return new self($result);
    }

    /**
     * @return $this
     */
    public function latest(): self
    {
        return $this->sortByVersion();
    }

    /**
     * @return $this
     */
    public function oldest(): self
    {
        return $this->sortByVersion(true);
    }

    /**
     * @return $this
     */
    public function stable(): self
    {
        return $this->stability('stable');
    }

    /**
     * @param string $stability
     * @return $this
     */
    public function stability(string $stability): self
    {
        return $this->filter(static fn(ReleaseInterface $rel): bool =>
            $rel->getStability() === Stability::STABILITY_STABLE
        );
    }
}