<?php

/**
 * This file is part of Info package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console;

use Spiral\RoadRunner\Console\Binary\Architecture;
use Spiral\RoadRunner\Console\Binary\OperatingSystem;
use Spiral\RoadRunner\Console\Client\Asset;
use Spiral\RoadRunner\Console\Client\ClientInterface;
use Spiral\RoadRunner\Console\Client\GitHubClient;
use Spiral\RoadRunner\Console\Client\Version;
use Spiral\RoadRunner\Console\Client\VersionsCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal GetBinaryCommand is an internal library class, please do not use it in your code.
 * @psalm-internal Spiral\Info\Console
 */
final class GetBinaryCommand extends Command
{
    /**
     * @var string
     */
    private const OPTION_VERSION_LATEST = '*';

    /**
     * @var string
     */
    private const OPTION_DESC_DIRECTORY =
        'Installation directory (default: Current working directory)'
    ;

    /**
     * @var string
     */
    private const ERROR_OPTION_DIRECTORY =
        'Invalid installation directory (--location=%s) ' .
        'option argument: Directory not found or not readable'
    ;

    /**
     * @var string
     */
    private const OPTION_DESC_OS = 'Required operating system (default: Current OS)';

    /**
     * @var string
     */
    private const ERROR_OPTION_OS = 'Invalid operating system (--os=%s) option argument (available: %s)';

    /**
     * @var string
     */
    private const OPTION_DESC_ARCH = 'Required processor architecture (default: Current processor architecture)';

    /**
     * @var string
     */
    private const ERROR_OPTION_ARCH = 'Invalid architecture (--arch=%s) option argument (available: %s)';

    /**
     * @var string
     */
    private const OPTION_DESC_VERSION = 'Required version of Info binaries (default: latest)';

    /**
     * @var string
     */
    private const ERROR_OPTION_VERSION =
        'Could not find any available RoadRunner binary version (--ver=%s) which meets passed criterion (available: %s)'
    ;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'get-binary';
    }

    /**
     * @return string[]
     */
    public function getUsages(): array
    {
        return [
            '$ rr ' . $this->getName() . ' --location=/path/to/binary --os=linux --arch=amd64 --ver="^2.0"'
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
        [$os, $arch] = [$this->resolveOperatingSystem($input), $this->resolveArchitecture($input)];

        $output->writeln('<comment>Find available RoadRunner versions...<comment>');

        $asset = $this->findAsset($input, $output, $os, $arch);

        if ($asset === null) {
            throw new \LogicException('Could not find any available RoadRunner version');
        }

        return self::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $os
     * @param string $arch
     * @return Asset|null
     */
    private function findAsset(InputInterface $input, OutputInterface $output, string $os, string $arch): ?Asset
    {
        $client = new GitHubClient();

        $asset = null;

        foreach ($this->resolveVersions($client, $input) as $version) {
            $output->writeln(\sprintf('<comment>Installation RoadRunner <info>%s</info><comment>', $version->version));

            $asset = $version->asset($os, $arch);

            if ($asset === null) {
                $output->writeln(\sprintf('<error>Can not find binary for %s (%s)</error>', $os, $arch));
            } else {
                $output->writeln(\sprintf('<comment>Binary downloading: <info>%s</info></comment>', $asset->name));
                break;
            }
        }

        return $asset;
    }

    /**
     * Resolves operating system option.
     *
     * @param InputInterface $input
     * @return string
     */
    private function resolveOperatingSystem(InputInterface $input): string
    {
        $os = $input->getOption('os') ?? OperatingSystem::current();

        if (! OperatingSystem::isValid($os)) {
            $message = \sprintf(self::ERROR_OPTION_OS, $os, \implode(', ', OperatingSystem::all()));

            throw new \InvalidArgumentException($message);
        }

        return $os;
    }

    /**
     * Resolves processor architecture option.
     *
     * @param InputInterface $input
     * @return string
     */
    private function resolveArchitecture(InputInterface $input): string
    {
        $arch = $input->getOption('arch') ?? Architecture::current();

        if (! Architecture::isValid($arch)) {
            $message = \sprintf(self::ERROR_OPTION_ARCH, $arch, \implode(', ', Architecture::all()));

            throw new \InvalidArgumentException($message);
        }

        return $arch;
    }

    /**
     * Resolves binary output directory.
     *
     * @param InputInterface $input
     * @return string
     */
    private function resolveOutputDirectory(InputInterface $input): string
    {
        $output = $input->getOption('location') ?? (\getcwd() ?: '.');

        if (! \is_dir($output)) {
            throw new \InvalidArgumentException(\sprintf(self::ERROR_OPTION_DIRECTORY, $output));
        }

        return $output;
    }

    /**
     * @param ClientInterface $client
     * @param InputInterface $input
     * @return VersionsCollection|Version[]
     */
    private function resolveVersions(ClientInterface $client, InputInterface $input): VersionsCollection
    {
        $needle = \strtolower($input->getOption('ver') ?? self::OPTION_VERSION_LATEST);

        $available = $client->getVersions()
            ->withAssets()
            ->sortByVersion();

        $filter = $available
            ->matched($needle)
        ;

        if ($filter->empty()) {
            throw new \UnexpectedValueException(\vsprintf(self::ERROR_OPTION_VERSION, [
                $needle,
                $this->availableToString($available)
            ]));
        }

        return $filter;
    }

    /**
     * @param VersionsCollection|Version[] $versions
     * @return string
     */
    private function availableToString(VersionsCollection $versions): string
    {
        $result = [];

        foreach ($versions as $version) {
            $result[] = $version->version;
        }

        return \implode(', ', $result);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption('location', 'l', InputOption::VALUE_OPTIONAL,self::OPTION_DESC_DIRECTORY);
        $this->addOption('ver', 'ver', InputOption::VALUE_OPTIONAL,self::OPTION_DESC_VERSION);

        $this->addOption('os', 'o', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_OS);
        $this->addOption('arch', 'a', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_ARCH);
    }
}
