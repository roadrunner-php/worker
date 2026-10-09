<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Psr\Log\LogLevel;
use Spiral\RoadRunner\Logger;
use Testo\Assert;
use Testo\Test;

#[Test]
final class LoggerTest
{
    public function testLogWritesFormattedMessage(): void
    {
        $logger = self::createLogger();

        $logger->log(LogLevel::INFO, 'Worker started', ['pid' => 42]);

        Assert::same($logger->messages, ['[php info] Worker started {"pid":42}']);
    }

    public function testLogWithoutContext(): void
    {
        $logger = self::createLogger();

        $logger->log(LogLevel::DEBUG, 'Hello');

        Assert::same($logger->messages, ['[php debug] Hello []']);
    }

    public function testLevelMethodsDelegateToLog(): void
    {
        $logger = self::createLogger();

        $logger->error('Failure', ['code' => 500]);
        $logger->warning('Careful');

        Assert::same($logger->messages, [
            '[php error] Failure {"code":500}',
            '[php warning] Careful []',
        ]);
    }

    public function testContextThatCannotBeJsonEncodedIsPrinted(): void
    {
        $logger = self::createLogger();

        $logger->log(LogLevel::NOTICE, 'Invalid', ['value' => \NAN]);

        Assert::same($logger->messages, ['[php notice] Invalid ' . \print_r(['value' => \NAN], true)]);
    }

    private static function createLogger(): Logger
    {
        return new class extends Logger {
            /** @var list<string> */
            public array $messages = [];

            protected function write(string $message): void
            {
                $this->messages[] = $message;
            }
        };
    }
}
