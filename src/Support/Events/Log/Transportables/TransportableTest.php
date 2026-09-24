<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;
use Tests\Fixtures\Support\Entities\Relayable\Events\Updated;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;

#[CoversClass(Transportable::class)]
final class TransportableTest extends TestCase
{
    #[Test]
    public function it_supports_swapping(): void
    {
        $this->assertTrue(Swapper::isSwappable(Transportable::class));
    }

    #[Test]
    public function it_keys_rows_by_alias(): void
    {
        $transportable = Transportable::create([
            'id' => 'relayable.updated',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        $this->assertSame('relayable.updated', $transportable->getKey());
        $this->assertSame(Updated::class, Transportable::find('relayable.updated')?->class);
    }

    #[Test]
    public function it_casts_transports_to_a_collection(): void
    {
        Transportable::create([
            'id' => 'relayable.updated',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        $transports = Transportable::find('relayable.updated')?->transports;

        $this->assertNotNull($transports);
        $this->assertSame([Mqtt::class], $transports->all());
    }

    #[Test]
    public function it_queries_rows_by_transport(): void
    {
        Transportable::create([
            'id' => 'relayable.updated',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        $this->assertSame(1, Transportable::whereJsonContains('transports', Mqtt::class)->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function it_announces_lifecycle_changes_through_package_events(): void
    {
        Event::fake([Events\Created::class, Events\Deleting::class]);

        Transportable::create([
            'id' => 'relayable.updated',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ])->delete();

        Event::assertDispatched(Events\Created::class);
        Event::assertDispatched(Events\Deleting::class);
    }

    #[Test]
    public function it_can_be_related_to_from_a_consumer_table(): void
    {
        Schema::create('consumer_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event');
            $table->foreign('event')->references('id')->on('event_log_transportables');
        });

        $transportable = Transportable::create([
            'id' => 'relayable.updated',
            'class' => Updated::class,
            'transports' => [Mqtt::class],
        ]);

        $subscription = ConsumerSubscription::create(['id' => (string) str()->uuid7(), 'event' => $transportable->getKey()]);

        $this->assertTrue($transportable->is($subscription->transportable));
    }
}

class ConsumerSubscription extends Model
{
    public $incrementing = false;

    protected $table = 'consumer_subscriptions';

    protected $keyType = 'string';

    protected $fillable = ['id', 'event'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Support\Events\Log\Transportables\Transportable, $this>
     */
    public function transportable(): BelongsTo
    {
        return $this->belongsTo(Transportable::class, 'event');
    }
}
