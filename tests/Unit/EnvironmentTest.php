<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Spiral\RoadRunner\Environment;
use Testo\Assert;
use Testo\Test;

#[Test]
final class EnvironmentTest
{
    public function testGetModeWithDefault(): void
    {
        $env = new Environment();
        Assert::equals($env->getMode(), '');
    }

    public function testGetModeWithValue(): void
    {
        $env = new Environment(['RR_MODE' => 'mode_value']);
        Assert::equals($env->getMode(), 'mode_value');
    }

    public function testGetRelayAddressWithDefault(): void
    {
        $env = new Environment();
        Assert::equals($env->getRelayAddress(), 'pipes');
    }

    public function testGetRelayAddressWithValue(): void
    {
        $env = new Environment(['RR_RELAY' => 'relay_value']);
        Assert::equals($env->getRelayAddress(), 'relay_value');
    }

    public function testGetRPCAddressWithDefault(): void
    {
        $env = new Environment();
        Assert::equals($env->getRPCAddress(), 'tcp://127.0.0.1:6001');
    }

    public function testGetRPCAddressWithValue(): void
    {
        $env = new Environment(['RR_RPC' => 'rpc_value']);
        Assert::equals($env->getRPCAddress(), 'rpc_value');
    }

    public function testGetVersionWithValue(): void
    {
        $env = new Environment(['RR_VERSION' => '3.0.0']);
        Assert::equals($env->getVersion(), '3.0.0');
    }

    public function testGetVersionWithDefault(): void
    {
        $env = new Environment();
        Assert::same($env->getVersion(), '');
    }

    public function testEmptyValueOverridesDefault(): void
    {
        $env = new Environment(['RR_RELAY' => '', 'RR_RPC' => '']);

        Assert::same($env->getRelayAddress(), '');
        Assert::same($env->getRPCAddress(), '');
    }

    public function testServerVariablesOverrideEnvVariables(): void
    {
        $backup = [$_ENV, $_SERVER];
        $_ENV['RR_RPC'] = 'tcp://env:6001';
        $_SERVER['RR_RPC'] = 'tcp://server:6001';

        try {
            $env = Environment::fromGlobals();
        } finally {
            [$_ENV, $_SERVER] = $backup;
        }

        Assert::same($env->getRPCAddress(), 'tcp://server:6001');
    }

    public function testFromGlobals(): void
    {
        $_ENV['RR_MODE'] = 'global_mode';
        $_SERVER['RR_RELAY'] = 'global_relay';
        $_SERVER['RR_VERSION'] = 'global_version';

        $env = Environment::fromGlobals();

        Assert::equals($env->getMode(), 'global_mode');
        Assert::equals($env->getRelayAddress(), 'global_relay');
        Assert::equals($env->getVersion(), 'global_version');
        Assert::equals($env->getRPCAddress(), 'tcp://127.0.0.1:6001');
    }
}
