<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Composer\InstalledVersions;
use Spiral\RoadRunner\Version;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class VersionTest
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
            '1.*',
        ];


        yield [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => '2.1.0',
                ],
            ],
            '2.1.0',
            '2.*',
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
            '1.*',
        ];

        yield [
            [
                'spiral/roadrunner-worker' => [
                    'pretty_version' => 'v1.8.0',
                ],
            ],
            '1.8.0',
            '1.*',
        ];

        yield 'calendar version' => [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => 'v2025.1.15',
                ],
            ],
            '2025.1.15',
            '2025.*',
        ];

        yield 'semver v3 after calendar versions' => [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => 'v3.0.0',
                ],
                'spiral/roadrunner-worker' => [
                    'pretty_version' => 'v3.7.0',
                ],
            ],
            '3.0.0',
            '3.*',
        ];

        yield 'v3 dev branch' => [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => '3.x-dev',
                ],
            ],
            '3.x-dev',
            '3.*',
        ];

        yield 'branch alias without dots' => [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => 'dev-master',
                ],
            ],
            'dev-master',
            '*',
        ];

        yield 'non-numeric major version' => [
            [
                'spiral/roadrunner' => [
                    'pretty_version' => 'dev-feature/1.x',
                ],
            ],
            'dev-feature/1.x',
            '*',
        ];

        yield 'package without pretty version' => [
            [
                'spiral/roadrunner' => [],
            ],
            '',
            '*',
        ];

        yield [
            [
                'spiral/roadrunner-http' => [
                    'pretty_version' => 'v1.8.0',
                ],
            ],
            Version::VERSION_FALLBACK,
            '*',
        ];

        yield [
            [],
            Version::VERSION_FALLBACK,
            '*',
        ];
    }

    #[DataProvider('provideVersions')]
    public function testGetVersion(array $versions, string $expectedVersion, string $expectedConstraint): void
    {
        InstalledVersions::reload([
            'versions' => $versions,
        ]);

        Assert::same(Version::current(), $expectedVersion);
        Assert::same(Version::constraint(), $expectedConstraint);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $ref = new \ReflectionClass(InstalledVersions::class);
        $ref->setStaticPropertyValue('canGetVendors', false);
    }
}
