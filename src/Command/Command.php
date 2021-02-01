<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Command;

use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Installer\Environment\Architecture;
use Spiral\RoadRunner\Installer\Environment\OperatingSystem;
use Spiral\RoadRunner\Installer\Repository\GitHub\GitHubRepository;
use Spiral\RoadRunner\Installer\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-import-type OperatingSystemType from OperatingSystem
 * @psalm-import-type ArchitectureType from Architecture
 */
abstract class Command extends BaseCommand
{
    /**
     * @var string
     */
    private const OPTION_DESC_OS = 'Required operating system (default: Current OS)';

    /**
     * @var string
     */
    private const NOTICE_OPTION_OS =
        'Notice: Possibly invalid operating system (--os=%s) option argument (available: %s)'
    ;

    /**
     * @var string
     */
    private const OPTION_DESC_ARCH = 'Required processor architecture (default: Current processor architecture)';

    /**
     * @var string
     */
    private const NOTICE_OPTION_ARCH =
        'Notice: Possibly invalid architecture (--arch=%s) option argument (available: %s)'
    ;

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        return GitHubRepository::createFromGlobals();
    }

    /**
     * Resolves operating system option.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return OperatingSystemType
     */
    #[ExpectedValues(valuesFromClass: OperatingSystem::class)]
    protected function getOperatingSystem(InputInterface $input, OutputInterface $output): string
    {
        /** @var OperatingSystemType $os */
        $os = $input->getOption('os') ?? OperatingSystem::createFromGlobals();

        if (! OperatingSystem::isValid($os)) {
            $message = \sprintf(self::NOTICE_OPTION_OS, $os, \implode(', ', OperatingSystem::all()));

            $output->writeln('<error> ' . $message . '</error>');
        }

        return $os;
    }

    /**
     * Resolves operating system option.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ArchitectureType
     */
    #[ExpectedValues(valuesFromClass: Architecture::class)]
    protected function getProcessorArchitecture(InputInterface $input, OutputInterface $output): string
    {
        /** @var ArchitectureType $arch */
        $arch = $input->getOption('arch') ?? Architecture::createFromGlobals();

        if (! Architecture::isValid($arch)) {
            $message = \sprintf(self::NOTICE_OPTION_ARCH, $arch, \implode(', ', Architecture::all()));

            $output->writeln('<error> ' . $message . '</error>');
        }

        return $arch;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->configureOperatingSystem();
        $this->configureProcessorArchitecture();
    }

    /**
     * @return void
     */
    private function configureOperatingSystem(): void
    {
        $this->addOption('os', 'o', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_OS);
    }

    /**
     * @return void
     */
    private function configureProcessorArchitecture(): void
    {
        $this->addOption('arch', 'a', InputOption::VALUE_OPTIONAL, self::OPTION_DESC_ARCH);
    }
}