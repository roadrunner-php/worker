<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Command;

use Spiral\RoadRunner\Installer\Archive\ArchiveInterface;
use Spiral\RoadRunner\Installer\Archive\Factory;
use Spiral\RoadRunner\Installer\Repository\AssetInterface;
use Spiral\RoadRunner\Installer\Repository\ReleaseInterface;
use Spiral\RoadRunner\Installer\Repository\ReleasesCollection;
use Spiral\RoadRunner\Installer\Repository\RepositoryInterface;
use Spiral\RoadRunner\Version;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GetBinaryCommand extends Command
{
    /**
     * @var string
     */
    private const OPTION_DESC_DIRECTORY =
        'Installation directory (default: Current working directory)';

    /**
     * @var string
     */
    private const ERROR_OPTION_DIRECTORY =
        'Invalid installation directory (--location=%s) ' .
        'option argument: Directory not found or not readable';

    /**
     * @var string
     */
    private const OPTION_DESC_VERSION = 'Required version of RoadRunner binaries (default: latest)';

    /**
     * @var string
     */
    private const ERROR_OPTION_VERSION =
        'Could not find any available RoadRunner binary version which meets criterion (--ver=%s). ' .
        'Available: %s';

    /**
     * @var string
     */
    private const ERROR_ENVIRONMENT =
        'Could not find any available RoadRunner binary version which meets criterion (--os=%s --arch=%s). ' .
        'Available: %s';

    /**
     * @return string[]
     */
    public function getUsages(): array
    {
        return [
            '$ rr ' . $this->getName() . ' --location=/path/to/binary --os=linux --arch=amd64 --ver="^2.0"',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'get-binary';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Install or update RoadRunner binary';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->getRepository();

        $output->write('  - <info>spiral/roadrunner</info>: Installation...');

        // List of all available releases
        $releases = $this->findAvailableReleases($repository, $input);

        // Matched asset
        [$asset, $release] = $this->findAsset($releases, $input, $output);

        $output->writeln(
            "\r" . '  - <info>spiral/roadrunner</info>' .
            ' (<comment>' . $release->getVersion() . '</comment>):' .
            ' Downloading <info>' . $asset->getName() . '</info>'
        );

        // Create archive
        $archive = $this->assetToArchive($asset, $output);

        foreach ($this->extract($archive, $input) as $file) {
            [$name, $path] = [$file->getFilename(), $file->getRealPath() ?: $file->getPathname()];

            $this->footer($output, $release->getVersion(), $name, $path);
        }


        return self::SUCCESS;
    }

    /**
     * @param RepositoryInterface $repo
     * @param InputInterface $input
     * @return ReleasesCollection
     */
    private function findAvailableReleases(RepositoryInterface $repo, InputInterface $input): ReleasesCollection
    {
        $constraint = \strtolower($input->getOption('ver') ?? Version::constraint());

        // All available releases
        $available = $repo->getReleases()
            ->sortByVersion()
        ;

        // With constraints
        $filtered = $available->satisfies($constraint);

        if ($filtered->empty()) {
            $message = \sprintf(self::ERROR_OPTION_VERSION, $constraint, $this->releaseVersionsToString($available));
            throw new \UnexpectedValueException($message);
        }


        return $filtered;
    }

    /**
     * @param ReleasesCollection $releases
     * @return string
     */
    private function releaseVersionsToString(ReleasesCollection $releases): string
    {
        $versions = $releases
            ->map(fn(ReleaseInterface $release) => $release->getVersion())
            ->toArray()
        ;

        return \implode(', ', $versions);
    }

    /**
     * @param ReleasesCollection $releases
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return array{0: AssetInterface, 1: ReleaseInterface}
     */
    private function findAsset(ReleasesCollection $releases, InputInterface $in, OutputInterface $out): array
    {
        [$os, $arch] = [$this->getOperatingSystem($in, $out), $this->getProcessorArchitecture($in, $out)];

        /** @var ReleaseInterface[] $filtered */
        $filtered = $releases->withAssets();

        foreach ($filtered as $release) {
            $assets = $release->getAssets()
                ->whereArchitecture($arch)
                ->whereOperatingSystem($os)
            ;

            if ($assets->empty()) {
                $message = '  - <info>spiral/roadrunner</info> (<comment>%s</comment> does not contain an assembly ' .
                    'that meets the specified criteria (--os=<comment>%s</comment> --arch=<comment>%s</comment>)';

                $out->writeln(\sprintf($message, $release->getVersion(), $os, $arch));
                continue;
            }

            return [$assets->first(), $release];
        }

        $message = \sprintf(self::ERROR_ENVIRONMENT, $os, $arch, $this->releaseVersionsToString($releases));
        throw new \UnexpectedValueException($message);
    }

    /**
     * @param AssetInterface $asset
     * @param OutputInterface $out
     * @param string|null $temp
     * @return ArchiveInterface
     * @throws \Throwable
     */
    private function assetToArchive(AssetInterface $asset, OutputInterface $out, string $temp = null): ArchiveInterface
    {
        $factory = new Factory();

        $progress = new ProgressBar($out);
        $progress->setFormat('  [%bar%] %percent:3s%% (%size%Kb/%total%Kb)');
        $progress->display();

        try {
            return $factory->fromAsset($asset, function (int $size, int $total) use (&$progress) {
                if ($progress->getMaxSteps() !== $total) {
                    $progress->setMaxSteps($total);
                }

                if ($progress->getStartTime() === 0) {
                    $progress->start();
                }

                $progress->setMessage(\number_format($size / 1000, 2), 'size');
                $progress->setMessage(\number_format($total / 1000, 2), 'total');

                $progress->setProgress($size);
            }, $temp);
        } finally {
            $progress->clear();
        }
    }

    /**
     * @param ArchiveInterface $archive
     * @param InputInterface $input
     * @return iterable<\SplFileInfo>
     */
    private function extract(ArchiveInterface $archive, InputInterface $input): iterable
    {
        $target = $this->getOutputDirectory($input);

        $files = ['rr' => $target . '/rr', 'rr.exe' => $target . '/rr.exe'];

        return $archive->extract($files);
    }

    /**
     * Resolves binary output directory.
     *
     * @param InputInterface $input
     * @return string
     */
    private function getOutputDirectory(InputInterface $input): string
    {
        $output = (string)($input->getOption('location') ?? (\getcwd() ?: '.'));

        if (! \is_dir($output)) {
            throw new \InvalidArgumentException(\sprintf(self::ERROR_OPTION_DIRECTORY, $output));
        }

        return $output;
    }

    /**
     * @param OutputInterface $output
     * @param string $version
     * @param string $name
     * @param string $path
     */
    private function footer(OutputInterface $output, string $version, string $name, string $path): void
    {
        $messages = [
            '  RoadRunner (<comment>' . $version . '</comment>) has been installed into <info>' . $path . '</info>',
            '',
            '  For more detailed documentation, see the <info><href=https://roadrunner.dev>https://roadrunner.dev</></info>',
            '  To run the application, use the following command:',
            '',
            '   <comment>$ ' . $name . ' serve</comment>',
            '',
        ];

        foreach ($messages as $line) {
            $output->writeln($line);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('location', 'l', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_DIRECTORY);
        $this->addOption('ver', 'ver', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_VERSION);

        parent::configure();
    }
}
