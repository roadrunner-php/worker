<?php

declare(strict_types=1);

namespace Spiral\RoadRunner;

enum Encoding
{
    case Raw;
    case Json;
    case Protobuf;
}
