<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Message;

/**
 * Marked message should be skipped in main worker loop.
 * For example {@see StreamStop} message has sense only in stream output.
 */
interface SkipMessage
{
}
