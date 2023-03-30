<?php

declare(strict_types=1);

namespace Spiral\RoadRunner;

use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Environment\Mode;

/**
 * @psalm-import-type ModeType from Mode
 * @psalm-type EnvironmentVariables = array {
 *      RR_MODE?:   ModeType|string,
 *      RR_RELAY?:  string,
 *      RR_RPC?:    string,
 * }
 * @see Mode
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

    #[ExpectedValues(valuesFromClass: Mode::class)]
    public function getMode(): string
    {
        return $this->get('RR_MODE', '');
    }

    public function getRelayAddress(): string
    {
        return $this->get('RR_RELAY', 'pipes');
    }

    public function getRPCAddress(): string
    {
        return $this->get('RR_RPC', 'tcp://127.0.0.1:6001');
    }

    /**
     * @template TDefault of string
     *
     * @param non-empty-string $name
     * @param TDefault $default
     * @return string|TDefault
     */
    private function get(string $name, string $default = ''): string
    {
        if (isset($this->env[$name]) || \array_key_exists($name, $this->env)) {
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
