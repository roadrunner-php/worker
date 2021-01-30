<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Command;

use Spiral\RoadRunner\Installer\Repository\ReleaseInterface;
use Spiral\RoadRunner\Installer\Repository\Stability;
use Spiral\RoadRunner\Version;
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
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Release', 'Stability', 'Binaries', 'Compatibility']);

        $versions = $this->getRepository()
            ->getReleases()
            ->sortByVersion();

        foreach ($versions as $version) {
            $table->addRow([
                $this->versionToString($version),
                $this->stabilityToString($version),
                $this->assetsToString($version),
                $this->compatibilityToString($version, $input, $output)
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }

    /**
     * @param ReleaseInterface $release
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return string
     */
    private function compatibilityToString(ReleaseInterface $release, InputInterface $in, OutputInterface $out): string
    {
        $template = '<fg=red> ✖ </> (reason: <comment>%s</comment>)';

        // Validate version
        if (! $release->satisfies(Version::constraint())) {
            return \sprintf($template, 'incompatible version');
        }

        // Validate assets
        $assets = $release->getAssets();

        if ($assets->empty()) {
            return \sprintf($template, 'no binaries');
        }

        // Validate OS
        $assets = $assets->whereOperatingSystem(
            $os = $this->getOperatingSystem($in, $out)
        );

        if ($assets->empty()) {
            return \sprintf($template, 'no assembly for ' . $os);
        }

        // Validate architecture
        $assets = $assets->whereArchitecture(
            $arch = $this->getProcessorArchitecture($in, $out)
        );

        if ($assets->empty()) {
            return \sprintf($template, 'no assembly for ' . $arch);
        }

        return '<fg=green> ✓ </>';
    }

    /**
     * @param ReleaseInterface $release
     * @return string
     */
    private function versionToString(ReleaseInterface $release): string
    {
        return $release->getVersion();
    }

    /**
     * @param ReleaseInterface $release
     * @return string
     */
    private function stabilityToString(ReleaseInterface $release): string
    {
        $stability = $release->getStability();

        switch ($stability) {
            case Stability::STABILITY_STABLE:
                return "<fg=green> $stability </>";

            case Stability::STABILITY_RC:
                return "<fg=blue> $stability </>";

            case Stability::STABILITY_BETA:
                return "<fg=yellow> $stability </>";

            case Stability::STABILITY_ALPHA:
                return "<fg=red> $stability </>";

            default:
                return "<bg=red;bg=white> $stability </>";
        }
    }

    /**
     * @param ReleaseInterface $release
     * @return string
     */
    private function assetsToString(ReleaseInterface $release): string
    {
        $count = $release->getAssets()
            ->count()
        ;

        if ($count > 0) {
            return \sprintf('<fg=green> ✓ </> (<comment>%d</comment>)', $count);
        }

        return '<fg=red> ✖ </>';
    }
}