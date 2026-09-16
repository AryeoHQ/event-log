<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Data;

use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Data\Version\Exceptions\NotProvided;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;

#[CoversClass(Variant::class)]
#[CoversClass(NotProvided::class)]
final class VariantTest extends TestCase
{
    #[Test]
    public function it_normalizes_json_serializable_payload(): void
    {
        $payload = new class implements JsonSerializable
        {
            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['key' => 'value'];
            }
        };

        $variant = Variant::make($payload, version: PayloadVersion::V1);

        $this->assertSame(['key' => 'value'], $variant->payload);
        $this->assertSame(PayloadVersion::V1, $variant->version);
    }

    #[Test]
    public function it_normalizes_arrayable_payload(): void
    {
        $variant = Variant::make(collect(['key' => 'value']), version: PayloadVersion::V1);

        $this->assertSame(['key' => 'value'], $variant->payload);
    }

    #[Test]
    public function it_discovers_version_from_payload_property(): void
    {
        $payload = new class implements JsonSerializable
        {
            public PayloadVersion $version = PayloadVersion::V2;

            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['discovered' => true];
            }
        };

        $variant = Variant::make($payload);

        $this->assertSame(PayloadVersion::V2, $variant->version);
        $this->assertSame(['discovered' => true], $variant->payload);
    }

    #[Test]
    public function it_prefers_discovered_version_over_explicit(): void
    {
        $payload = new class implements JsonSerializable
        {
            public PayloadVersion $apiVersion = PayloadVersion::V2;

            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['data' => true];
            }
        };

        $variant = Variant::make($payload, version: PayloadVersion::V1);

        $this->assertSame(PayloadVersion::V2, $variant->version);
    }

    #[Test]
    public function it_falls_back_to_explicit_version(): void
    {
        $payload = new class implements JsonSerializable
        {
            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['no_version_property' => true];
            }
        };

        $variant = Variant::make($payload, version: PayloadVersion::V1);

        $this->assertSame(PayloadVersion::V1, $variant->version);
    }

    #[Test]
    public function it_throws_when_no_version_is_resolvable(): void
    {
        $this->expectException(NotProvided::class);

        $payload = new class implements JsonSerializable
        {
            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return ['orphan' => true];
            }
        };

        Variant::make($payload);
    }
}
