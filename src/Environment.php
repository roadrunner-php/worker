<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner;

use Spiral\RoadRunner\Exception\EnvironmentException;

/**
 * @psalm-type EnvironmentVariables = array<string, string> | array {
 *      RR_MODE?:   string,
 *      RR_RELAY?:  string,
 *      RR_RPC?:    string,
 * }
 */
class Environment implements EnvironmentInterface
{
    /**
     * @var EnvironmentVariables
     */
    private array $env;

    /**
     * @param EnvironmentVariables $env
     */
    public function __construct(array $env = [])
    {
        $this->env = $env;
    }

    /**
     * Returns worker mode assigned to the PHP process.
     *
     * @return string
     * @throws EnvironmentException
     */
    public function getMode(): string
    {
        return $this->get('RR_MODE');
    }

    /**
     * Address worker should be connected to (or pipes).
     *
     * @return string
     * @throws EnvironmentException
     */
    public function getRelayAddress(): string
    {
        return $this->get('RR_RELAY', 'pipes');
    }

    /**
     * RPC address.
     *
     * @return string
     * @throws EnvironmentException
     */
    public function getRPCAddress(): string
    {
        return $this->get('RR_RPC', 'tcp://127.0.0.1:6001');
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    private function get(string $name, string $default = ''): string
    {
        if (isset($this->env[$name]) || \array_key_exists($name, $this->env)) {
            /** @psalm-suppress RedundantCastGivenDocblockType */
            return (string)$this->env[$name];
        }

        return $default;
    }

    /**
     * @return self
     */
    public static function fromGlobals(): self
    {
        /** @var array<string, string> $env */
        $env = \array_merge($_ENV, $_SERVER);

        return new self($env);
    }
}
