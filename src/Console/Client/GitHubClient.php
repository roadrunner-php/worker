<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Client;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal GitHubClient is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\RoadRunner\Console
 */
final class GitHubClient extends Client
{
    /**
     * @var string
     */
    private const URL_RELEASES = 'https://api.github.com/repos/spiral/roadrunner/releases';

    /**
     * @var string
     */
    private const DEFAULT_HEADERS = [
        'accept' => 'application/vnd.github.v3+json'
    ];

    /**
     * {@inheritDoc}
     * @throws ExceptionInterface
     */
    public function getVersions(): VersionsCollection
    {
        return new VersionsCollection(
            \iterator_to_array($this->readVersions(), true)
        );
    }

    /**
     * @return \Traversable<Version>|Version[]
     * @throws ExceptionInterface
     * @noinspection PhpImmutablePropertyIsWrittenInspection
     */
    private function readVersions(): \Traversable
    {
        for ($page = 1; $page < \PHP_INT_MAX; ++$page) {
            $response = $this->get(self::URL_RELEASES, ['query' => ['page' => $page++]]);

            foreach ($response->toArray() as $entry) {
                $assets = $entry['assets'];

                $version = new GitHubVersion($entry['name'], [...$this->parseAssets($assets)]);
                $version->touch(new \DateTimeImmutable($entry['created_at']));

                yield $version->name => $version;
            }

            if (! $this->hasNext($response)) {
                break;
            }
        }
    }

    /**
     * @noinspection PhpImmutablePropertyIsWrittenInspection
     *
     * @param array $assets
     * @return \Traversable<Asset>
     * @throws \Exception
     */
    private function parseAssets(array $assets): \Traversable
    {
        foreach ($assets as $asset) {
            $binary = new Asset($asset['name'], $asset['browser_download_url']);
            $binary->created = new \DateTimeImmutable($asset['created_at']);
            $binary->updated = new \DateTimeImmutable($asset['updated_at']);

            yield $binary;
        }
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function hasNext(ResponseInterface $response): bool
    {
        $headers = $response->getHeaders();
        $link = $headers['link'] ?? [];

        if (! isset($link[0])) {
            return false;
        }

        return \str_contains($link[0], 'rel="next"');
    }

    /**
     * {@inheritDoc}
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        // Merge headers with defaults
        $options['headers'] = \array_merge(self::DEFAULT_HEADERS, $options['headers'] ?? []);

        return parent::request($method, $uri, $options);
    }
}