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
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        parent::__construct($name ?? 'get-binary');
    }

    /**
     * {@inheritDoc}
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
    public function getDescription(): string
    {
        return 'Install or update RoadRunner binary';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $this->getOutputDirectory($input);

        $repository = $this->getRepository();

        $output->write(\sprintf('  - <info>%s</info>: Installation...', $repository->getName()));

        // List of all available releases
        $releases = $this->findAvailableReleases($repository, $input);

        // Matched asset
        [$asset, $release] = $this->findAsset($repository, $releases, $input, $output);

        $output->writeln(
            \sprintf("\r  - <info>%s</info>", $repository->getName()) .
            \sprintf(' (<comment>%s</comment>):', $release->getVersion()) .
            \sprintf(' Downloading <info>%s</info>', $asset->getName())
        );

        // Install rr binary
        $name = $this->installBinary($target, $release, $asset, $input, $output);

        $this->installConfig($target, $release, $input, $output);

        $this->footer($output, $name ?? 'rr');

        return self::SUCCESS;
    }

    /**
     * @param string $target
     * @param ReleaseInterface $release
     * @param AssetInterface $asset
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return string|null
     * @throws \Throwable
     */
    private function installBinary(
        string $target,
        ReleaseInterface $release,
        AssetInterface $asset,
        InputInterface $in,
        OutputInterface $out
    ): ?string {
        $extractor = $this->assetToArchive($asset, $out)
            ->extract([
                'rr.exe' => $target . '/rr.exe',
                'rr'     => $target . '/rr',
            ])
        ;

        $name = null;
        while ($extractor->valid()) {
            $file = $extractor->current();
            $name = $file->getFilename();

            if (! $this->checkExisting($file, $in, $out)) {
                $extractor->send(false);
                continue;
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            $message = '  RoadRunner (<comment>%s</comment>) has been installed into <info>%s</info>';

            $out->writeln(\sprintf($message, $release->getVersion(), $path));

            $extractor->next();
        }

        return $name;
    }

    /**
     * @param string $to
     * @param ReleaseInterface $from
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return bool
     */
    private function installConfig(string $to, ReleaseInterface $from, InputInterface $in, OutputInterface $out): bool
    {
        $to .= '/.rr.yaml';

        if (\is_file($to)) {
            return false;
        }

        $question = new ConfirmationQuestion(
            '  Do you want create default ".rr.yaml" configuration file ? [Y/n] '
        );

        if (! $this->getHelper('question')
            ->ask($in, $out, $question)) {
            return false;
        }

        \file_put_contents($to, $from->getConfig());

        return true;
    }

    /**
     * @param \SplFileInfo $binary
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function checkExisting(\SplFileInfo $binary, InputInterface $input, OutputInterface $output): bool
    {
        if (\is_file($binary->getPathname())) {
            $output->writeln(' <error> RoadRunner binary file already exists! </error>');

            $question = new ConfirmationQuestion('  Do you want overwrite it? [Y/n] ');

            if (! $this->getHelper('question')
                ->ask($input, $output, $question)) {
                $output->writeln(\sprintf('  Skipping RoadRunner (<comment>%s</comment>) installation...',
                    $binary->getRealPath()));
                return false;
            }
        }

        return true;
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
     * @param RepositoryInterface $repo
     * @param ReleasesCollection $releases
     * @param InputInterface $in
     * @param OutputInterface $out
     * @return array{0: AssetInterface, 1: ReleaseInterface}
     */
    private function findAsset(
        RepositoryInterface $repo,
        ReleasesCollection $releases,
        InputInterface $in,
        OutputInterface $out
    ): array {
        [$os, $arch] = [$this->getOperatingSystem($in, $out), $this->getProcessorArchitecture($in, $out)];

        /** @var ReleaseInterface[] $filtered */
        $filtered = $releases->withAssets();

        foreach ($filtered as $release) {
            $assets = $release->getAssets()
                ->whereArchitecture($arch)
                ->whereOperatingSystem($os)
            ;

            if ($assets->empty()) {
                // Notice
                $out->writeln('');

                $message = '  <fg=white;bg=yellow> %s %s does not contain available assembly (further search in progress) </>';
                $out->writeln(\sprintf($message, $repo->getName(), $release->getVersion()));
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
        $progress->setMessage('0.00', 'size');
        $progress->setMessage('?.??', 'total');
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
     * @param string $name
     */
    private function footer(OutputInterface $output, string $name): void
    {
        $messages = [
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
