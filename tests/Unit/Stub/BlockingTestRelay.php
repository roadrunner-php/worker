<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit\Stub;

use Spiral\Goridge\BlockingRelayInterface;

final class BlockingTestRelay extends TestRelay implements BlockingRelayInterface {}
