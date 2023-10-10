<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Payload;

final class PayloadTest extends TestCase
{
    public function testPayloadConstructionWithValues(): void
    {
        $payload = new Payload('body_content', 'header_content', false);

        $this->assertEquals('body_content', $payload->body);
        $this->assertEquals('header_content', $payload->header);
        $this->assertFalse($payload->eos);
    }

    public function testPayloadConstructionWithDefaultValues(): void
    {
        $payload = new Payload(null, null);

        $this->assertEquals('', $payload->body);
        $this->assertEquals('', $payload->header);
        $this->assertTrue($payload->eos);
    }

    public function testPayloadConstructionWithPartialValues(): void
    {
        $payload = new Payload('body_content');

        $this->assertEquals('body_content', $payload->body);
        $this->assertEquals('', $payload->header);
        $this->assertTrue($payload->eos);
    }

    public function testPayloadConstructionWithEosFalse(): void
    {
        $payload = new Payload(null, null, false);

        $this->assertEquals('', $payload->body);
        $this->assertEquals('', $payload->header);
        $this->assertFalse($payload->eos);
    }
}