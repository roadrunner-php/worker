<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Message\Command;

use Spiral\RoadRunner\Message\SkipMessage;
use Spiral\RoadRunner\Payload;

final class StreamStop extends Payload implements SkipMessage
{
}
