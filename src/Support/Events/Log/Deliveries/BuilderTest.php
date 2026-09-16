<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Envelopes\Envelope;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    #[Test]
    public function it_matches_a_delivery_by_its_envelope(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient);

        $this->assertTrue(
            $delivery->is(Delivery::where(['envelope' => $envelope])->first())
        );
    }

    #[Test]
    public function it_matches_a_delivery_by_its_envelope_in_the_string_form(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient);

        $this->assertTrue(
            $delivery->is(Delivery::where('envelope', $envelope)->first())
        );
    }

    #[Test]
    public function it_does_not_match_a_delivery_with_a_different_version(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient, version: PayloadVersion::V2);

        $this->assertNull(Delivery::where(['envelope' => $envelope])->first());
    }

    #[Test]
    public function it_leaves_non_envelope_conditions_untouched(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $this->assertTrue(
            $delivery->is(Delivery::where(['recipient_id' => $delivery->recipient_id])->first())
        );
    }

    #[Test]
    public function it_excludes_a_delivery_matching_the_envelope_when_negated(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient);

        $this->assertNull(Delivery::whereNot('envelope', $envelope)->first());
    }

    #[Test]
    public function it_keeps_a_delivery_not_matching_the_envelope_when_negated(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $other = Envelope::make(recipient: Recordable::factory()->create());

        $this->assertTrue(
            $delivery->is(Delivery::whereNot('envelope', $other)->first())
        );
    }

    #[Test]
    public function it_excludes_a_delivery_matching_the_envelope_with_a_not_equal_operator(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient);

        $this->assertNull(Delivery::where('envelope', '!=', $envelope)->first());
    }

    #[Test]
    public function it_matches_an_envelope_within_a_nested_closure(): void
    {
        $delivery = Delivery::factory()->mqtt()->createQuietly();

        $envelope = Envelope::make(recipient: $delivery->recipient);

        $this->assertTrue(
            $delivery->is(
                Delivery::where(fn (Builder $query) => $query->where('envelope', $envelope))->first()
            )
        );
    }

    #[Test]
    public function it_matches_either_envelope_when_combined_with_or(): void
    {
        $first = Delivery::factory()->mqtt()->createQuietly();
        $second = Delivery::factory()->mqtt()->createQuietly();

        $found = Delivery::where('envelope', Envelope::make(recipient: $first->recipient))
            ->orWhere('envelope', Envelope::make(recipient: $second->recipient))
            ->get();

        $this->assertCount(2, $found);
    }
}
