<?php

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\Goridge\Frame;
use Spiral\RoadRunner\Encoding;
use Spiral\RoadRunner\PayloadFactory;

class PayloadFactoryTest extends TestCase
{
    public function testFromFrameBasicCase()
    {
        $header = '{"Foo": ["bar"]}';
        $body = '123';
        $payload = PayloadFactory::fromFrame(
            new Frame($header . $body, [\strlen($header)])
        );

        $this->assertSame(Encoding::Raw, $payload->encoding);
        $this->assertSame($header, $payload->header);
        $this->assertSame($body, $payload->body);
    }

    public function testFromFrameProtoEncoding()
    {
        $header = \random_bytes(32);
        $body = \random_bytes(64);
        $payload = PayloadFactory::fromFrame(
            new Frame($header . $body, [\strlen($header)], Frame::CODEC_PROTO)
        );

        $this->assertSame(Encoding::Protobuf, $payload->encoding);
        $this->assertSame($header, $payload->header);
        $this->assertSame($body, $payload->body);
    }

    public function testFromFrameJsonEncoding()
    {
        $header = \json_encode(['foo' => ['bar']], \JSON_THROW_ON_ERROR);
        $body = \random_bytes(64);
        $payload = PayloadFactory::fromFrame(
            new Frame($header . $body, [\strlen($header)], Frame::CODEC_JSON)
        );

        $this->assertSame(Encoding::Json, $payload->encoding);
        $this->assertSame($header, $payload->header);
        $this->assertSame($body, $payload->body);
    }
}
