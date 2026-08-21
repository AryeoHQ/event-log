<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Data;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;

#[CoversClass(Data::class)]
#[CoversClass(Variant::class)]
final class DataTest extends TestCase
{
    #[Test]
    public function it_serializes_single_variant_to_keyed_json(): void
    {
        Recordable::factory()->create()->announceToLog();

        $raw = Log::first()->getRawOriginal('data');

        $decoded = json_decode($raw, true);

        $this->assertArrayHasKey(PayloadVersion::V1->value, $decoded);
        $this->assertArrayHasKey('id', $decoded[PayloadVersion::V1->value]);
    }

    #[Test]
    public function it_serializes_variants_to_keyed_json(): void
    {
        $log = Log::factory()->make();
        $log->forceFill([
            'data' => Data::of(
                Variant::make(collect(['order_id' => 1]), version: PayloadVersion::V1),
                Variant::make(collect(['order_id' => 1, 'total' => 100]), version: PayloadVersion::V2),
            ),
        ]);

        $raw = $log->getAttributes()['data'];

        $decoded = json_decode($raw, true);

        $this->assertSame(['order_id' => 1], $decoded['v1']);
        $this->assertSame(['order_id' => 1, 'total' => 100], $decoded['v2']);
    }

    #[Test]
    public function it_rejects_non_variance_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $log = Log::factory()->make();
        $log->forceFill([
            'data' => ['foo' => 'bar'],
        ]);
    }

    #[Test]
    public function it_returns_plain_array_on_get(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $data = Log::first()->fresh()->data;

        $this->assertIsArray($data);
        $this->assertArrayHasKey(PayloadVersion::V1->value, $data);
        $this->assertSame($recordable->getKey(), $data[PayloadVersion::V1->value]['id']);
    }
}
