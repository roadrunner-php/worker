<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console;

use Composer\Semver\VersionParser;
use Spiral\RoadRunner\Console\Client\GitHubClient;
use Spiral\RoadRunner\Console\Client\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionsCommand extends Command
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'versions';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Returns a list of all available RoadRunner versions';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Version', 'Stability', 'Binaries', 'Release Date']);


        $versions = (new GitHubClient())
            ->getVersions()
            ->sortByVersion();

        foreach ($versions as $version) {
            $table->addRow([
                $this->versionToString($version),
                $this->stabilityToString($version),
                $this->assetsToString($version),
                $version->created->format('Y-m-d')
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }

    /**
     * @param Version $version
     * @return string
     */
    private function versionToString(Version $version): string
    {
        return $version->version;
    }

    /**
     * @param Version $version
     * @return string
     */
    private function stabilityToString(Version $version): string
    {
        $stability = $version->stability;

        switch ($stability) {
            case 'stable':
                return "<fg=green>$stability</>";

            case 'beta':
                return "<fg=yellow>$stability</>";

            default:
                return "<fg=red>$stability</>";
        }
    }

    /**
     * @param Version $version
     * @return string
     */
    private function assetsToString(Version $version): string
    {
        return $version->hasAssets() ? '<info> ✓ </info>' : '<fg=red> ✖ </>';
    }
}