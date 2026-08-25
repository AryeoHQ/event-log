<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Concerns\HasEvent;
use Support\Events\Log\Logs\Integrity\Corrupted;
use Support\Events\Log\Logs\Integrity\Tampered;
use Support\Events\Log\Logs\Status\Status;
use Tests\Fixtures\Support\Entities\Recordable\Events\Updated;
use Tests\Fixtures\Support\Entities\Recordable\Recordable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;
use Tests\TestCase;
use TypeError;

#[CoversClass(Log::class)]
#[CoversClass(Corrupted::class)]
#[CoversClass(Tampered::class)]
#[CoversTrait(HasEvent::class)]
final class LogTest extends TestCase
{
    #[Test]
    public function it_sets_event_when_recordable_received(): void
    {
        $log = Log::factory()->state([
            'event' => new Updated(Recordable::factory()->create()),
        ])->make();

        $this->assertInstanceOf(Updated::class, $log->event);
    }

    #[Test]
    public function it_round_trips_event_with_hmac(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $this->assertInstanceOf(Updated::class, Log::first()->fresh()->event);
    }

    #[Test]
    public function it_returns_tampered_when_event_payload_is_tampered(): void
    {
        $log = Log::factory()->state([
            'event' => new Updated(Recordable::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ])->create();

        $tampered = with(
            $log->getRawOriginal('event'),
            fn (string $raw) => substr($raw, 0, 64).'TAMPERED'.substr($raw, 72)
        );
        $log->setRawAttributes(array_merge($log->getAttributes(), ['event' => $tampered]));

        $this->assertInstanceOf(Tampered::class, $log->event);
    }

    #[Test]
    public function it_verifies_event_signed_with_a_rotated_key(): void
    {
        $log = Log::factory()->state([
            'event' => new Updated(Recordable::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ])->create();

        tap(config('app.key'), function (string $oldKey): void {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
            config(['app.previous_keys' => [$oldKey]]);
        });

        $this->assertInstanceOf(Updated::class, $log->fresh()->event);
    }

    #[Test]
    #[WithConfig('app.key', null)]
    public function it_throws_when_no_signing_key_is_set(): void
    {
        $this->expectException(MissingAppKeyException::class);

        Log::factory()->state([
            'event' => new Updated(Recordable::factory()->create()),
        ])->make();
    }

    #[Test]
    public function it_returns_corrupted_when_event_payload_is_not_valid_base64(): void
    {
        $log = Log::factory()->state([
            'event' => new Updated(Recordable::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ])->create();

        $log->setRawAttributes(array_merge($log->getAttributes(), ['event' => '!!!not-base64!!!']));

        $this->assertInstanceOf(Corrupted::class, $log->event);
    }

    #[Test]
    public function it_throws_when_event_is_set_as_unsupported_type(): void
    {
        $this->expectException(TypeError::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->state([
            'event' => new class {},
        ])->make();
    }

    #[Test]
    public function it_throws_when_context_is_set_as_unsupported_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->state([
            'context' => 'not-a-repository',
        ])->make();
    }

    #[Test]
    public function it_sets_context_when_context_received(): void
    {
        $log = Log::factory()->state([
            'context' => app(\Illuminate\Log\Context\Repository::class)->add(['foo' => 'bar']),
        ])->make();

        $this->assertInstanceOf(\Illuminate\Log\Context\Repository::class, $log->context);
    }

    #[Test]
    public function it_sets_context_when_whitelisted_received(): void
    {
        $log = Log::factory()->state([
            'context' => new \Support\Events\Log\Context\Whitelisted,
        ])->make();

        $this->assertInstanceOf(\Support\Events\Log\Context\Whitelisted::class, $log->context);
    }

    #[Test]
    public function it_guards_type(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->make()->fill([
            'type' => 'recordable.updated',
        ]);
    }

    #[Test]
    public function it_guards_data(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->make()->fill([
            'data' => ['foo' => 'bar'],
        ]);
    }

    #[Test]
    public function it_guards_loggable(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->make()->fill([
            'loggable' => Recordable::factory()->create(),
        ]);
    }

    #[Test]
    public function it_guards_loggable_id(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->make()->fill([
            'loggable_id' => 'some-id',
        ]);
    }

    #[Test]
    public function it_guards_loggable_type(): void
    {
        $this->expectException(MassAssignmentException::class);

        Model::preventSilentlyDiscardingAttributes();
        Log::factory()->make()->fill([
            'loggable_type' => 'recordable',
        ]);
    }

    #[Test]
    public function it_relates_to_loggable(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $this->assertSame($recordable->getKey(), Log::first()->loggable->getKey());
    }

    #[Test]
    public function it_unsets_loggable_appends_when_stored(): void
    {
        $recordable = Recordable::factory()->create();

        $this->assertArrayHasKey('preview', $recordable->toArray());

        $recordable->announceToLog();

        $event = Log::first()->event;

        $this->assertArrayNotHasKey('preview', Log::first()->event->{$event->loggableProperty}->toArray());
    }

    #[Test]
    public function it_unsets_loggable_relations_when_stored(): void
    {
        Recordable::factory()->create();

        $recordable = Recordable::with('nonRecordable')->first();
        $this->assertArrayHasKey('non_recordable', $recordable->toArray());

        $recordable->announceToLog();

        $event = Log::first()->event;

        $this->assertArrayNotHasKey('non_recordable', Log::first()->event->{$event->loggableProperty}->toArray());
    }

    #[Test]
    public function it_records_data_from_variance_to_loggable(): void
    {
        $recordable = Recordable::factory()->create();

        $recordable->announceToLog();

        $data = Log::first()->fresh()->data;

        $this->assertArrayHasKey(PayloadVersion::V1->value, $data);
        $this->assertSame($recordable->getKey(), data_get($data, PayloadVersion::V1->value.'.id'));
        $this->assertSame($recordable->title, data_get($data, PayloadVersion::V1->value.'.title'));
    }

    #[Test]
    public function it_defaults_status_to_pending(): void
    {
        $log = Log::factory()->make();

        $this->assertSame(Status::Pending, $log->status->enum);
    }

    #[Test]
    #[WithConfig('event_log.queues.'.Log::class, 'logs')]
    public function it_resolves_the_configured_queue(): void
    {
        $this->assertSame('logs', Log::factory()->make()->queue);
    }

    #[Test]
    public function it_resolves_no_queue_when_nothing_is_configured(): void
    {
        $this->assertNull(Log::factory()->make()->queue);
    }
}
