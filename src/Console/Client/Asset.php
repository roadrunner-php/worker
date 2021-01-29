<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Client;

use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class Asset
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $url;

    /**
     * @var \DateTimeInterface
     */
    public \DateTimeInterface $created;

    /**
     * @var \DateTimeInterface
     */
    public \DateTimeInterface $updated;

    /**
     * @param string $name
     * @param string $url
     */
    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;

        $this->created = $this->updated = new \DateTimeImmutable();
    }
}