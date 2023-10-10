<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Version;

final class VersionTest extends TestCase
{
    public static function provideVersions(): iterable
    {
        yield [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => 'v1.9.0',
                ],
                'spiral/roadrunner-worker' => [
                    'pretty_version' => 'v1.8.0',
                ],
            ],
            '1.9.0',
            '1.*'
        ];


        yield [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => '2.1.0',
                ],
            ],
            '2.1.0',
            '2.*'
        ];

        yield [
            [
                'spiral/roadrunner-worker' => [
                    'pretty_version' => 'v1.8.0',
                ],
                'spiral/roadrunner' => [
                    'pretty_version' => 'v1.9.0',
                ],
            ],
            '1.9.0',
            '1.*'
        ];

        yield [
            [
                'spiral/roadrunner-worker' => [
                    'pretty_version' => 'v1.8.0',
                ],
            ],
            '1.8.0',
            '1.*'
        ];

        yield [
            [
                'spiral/roadrunner-http' => [
                    'pretty_version' => 'v1.8.0',
                ],
            ],
            Version::VERSION_FALLBACK,
            '*'
        ];

        yield [
            [],
            Version::VERSION_FALLBACK,
            '*'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionClass(InstalledVersions::class);
        $ref->setStaticPropertyValue('canGetVendors', false);
    }

    #[DataProvider('provideVersions')]
    public function testGetVersion(array $versions, string $expectedVersion, string $expectedConstraint): void
    {
        InstalledVersions::reload([
            'versions' => $versions,
        ]);

        $this->assertSame($expectedVersion, Version::current());
        $this->assertSame($expectedConstraint, Version::constraint());
    }
}