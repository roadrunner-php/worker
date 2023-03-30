<?php

declare(strict_types=1);

namespace Spiral\RoadRunner;

use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Environment\Mode;

/**
 * Provides base values to configure roadrunner worker.
 *
 * @psalm-import-type ModeType from Mode
 * @see Mode
 */
interface EnvironmentInterface
{
    /**
     * Returns worker mode assigned to the PHP process.
     *
     * @return ModeType
     */
    #[ExpectedValues(valuesFromClass: Mode::class)]
    public function getMode(): string;

    /**
     * Address worker should be connected to (or pipes).
     *
     * @return non-empty-string
     */
    public function getRelayAddress(): string;

    /**
     * RPC address.
     *
     * @return non-empty-string
     */
    public function getRPCAddress(): string;
}
