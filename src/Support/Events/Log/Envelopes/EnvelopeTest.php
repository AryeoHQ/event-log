<?php

declare(strict_types=1);

namespace Support\Events\Log\Envelopes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;

#[CoversClass(Envelope::class)]
final class EnvelopeTest extends TestCase
{
    #[Test]
    public function it_holds_a_version_and_recipient(): void
    {
        $recipient = Recordable::factory()->create();

        $envelope = Envelope::make(recipient: $recipient, version: PayloadVersion::V1);

        $this->assertSame(PayloadVersion::V1, $envelope->version);
        $this->assertTrue($recipient->is($envelope->recipient));
    }

    #[Test]
    public function it_defaults_to_no_version(): void
    {
        $recipient = Recordable::factory()->create();

        $envelope = Envelope::make(recipient: $recipient);

        $this->assertNull($envelope->version);
        $this->assertTrue($recipient->is($envelope->recipient));
    }
}
