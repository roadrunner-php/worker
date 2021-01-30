<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Installer\Repository\GitHub;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Factory
{
    /**
     * @var string
     */
    public const ENV_GITHUB_REPO_OWNER = 'GITHUB_REPO_OWNER';

    /**
     * @var string
     */
    public const ENV_GITHUB_REPO_NAME = 'GITHUB_REPO_NAME';

    /**
     * @var string
     */
    public const ENV_GITHUB_TOKEN = 'GITHUB_TOKEN';

    /**
     * @var HttpClientInterface|null
     */
    private ?HttpClientInterface $client;

    /**
     * @param HttpClientInterface|null $client
     */
    public function __construct(HttpClientInterface $client = null)
    {
        $this->client = $client;
    }

    /**
     * @param array|null $variables
     * @return GitHubRepository
     */
    public function createFromGlobals(array $variables = null): GitHubRepository
    {
        // Fetch repository owner
        $owner = $this->env($variables, static::ENV_GITHUB_REPO_OWNER, GitHubRepository::OFFICIAL_REPOSITORY_OWNER);

        // Fetch repository name
        $name = $this->env($variables, static::ENV_GITHUB_REPO_NAME, GitHubRepository::OFFICIAL_REPOSITORY_NAME);

        // Fetch access token
        $token = $this->env($variables, static::ENV_GITHUB_TOKEN, null);

        $client = $this->client ?? HttpClient::create([
            'headers' => \array_filter([
                'authorization' => $token ? 'token ' . $token : null
            ])
        ]);

        return new GitHubRepository($owner, $name, $client);
    }

    /**
     * @param array|null $variables
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function env(?array $variables, string $key, $default)
    {
        return $variables[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}