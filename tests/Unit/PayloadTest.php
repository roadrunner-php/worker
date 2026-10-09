<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tests\Worker\Unit;

use Testo\Test;
use Testo\Assert;
use Spiral\RoadRunner\Payload;

#[Test]
final class PayloadTest
{
    public function testPayloadConstructionWithValues(): void
    {
        $payload = new Payload('body_content', 'header_content', false);

        Assert::equals($payload->body, 'body_content');
        Assert::equals($payload->header, 'header_content');
        Assert::false($payload->eos);
    }

    public function testPayloadConstructionWithDefaultValues(): void
    {
        $payload = new Payload(null, null);

        Assert::equals($payload->body, '');
        Assert::equals($payload->header, '');
        Assert::true($payload->eos);
    }

    public function testPayloadConstructionWithPartialValues(): void
    {
        $payload = new Payload('body_content');

        Assert::equals($payload->body, 'body_content');
        Assert::equals($payload->header, '');
        Assert::true($payload->eos);
    }

    public function testPayloadConstructionWithEosFalse(): void
    {
        $payload = new Payload(null, null, false);

        Assert::equals($payload->body, '');
        Assert::equals($payload->header, '');
        Assert::false($payload->eos);
    }
}