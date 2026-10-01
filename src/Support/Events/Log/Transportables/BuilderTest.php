<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Amqp\Amqp;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;

#[CoversClass(Builder::class)]
final class BuilderTest extends TestCase
{
    #[Test]
    public function it_matches_a_single_transport(): void
    {
        $mqtt = Transportable::factory()->create(['transports' => [Mqtt::class]]);
        Transportable::factory()->create(['transports' => [Amqp::class]]);

        $this->assertSame([$mqtt->getKey()], Transportable::query()->transportedByAny(Mqtt::class)->pluck('id')->all());
    }

    #[Test]
    public function it_matches_any_of_an_array_of_transports(): void
    {
        $mqtt = Transportable::factory()->create(['transports' => [Mqtt::class]]);
        $amqp = Transportable::factory()->create(['transports' => [Amqp::class]]);

        $this->assertEqualsCanonicalizing(
            [$mqtt->getKey(), $amqp->getKey()],
            Transportable::query()->transportedByAny([Mqtt::class, Amqp::class])->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_does_not_match_rows_without_any_of_the_transports(): void
    {
        Transportable::factory()->create(['transports' => []]);

        $this->assertSame(0, Transportable::query()->transportedByAny([Mqtt::class, Amqp::class])->count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function it_keeps_the_ors_grouped_when_chained(): void
    {
        Transportable::factory()->create(['transports' => [Mqtt::class]]);
        $amqp = Transportable::factory()->create(['transports' => [Amqp::class]]);

        $this->assertSame(
            [$amqp->getKey()],
            Transportable::query()->transportedByAny([Mqtt::class, Amqp::class])->where('id', $amqp->getKey())->pluck('id')->all(),
        );
    }
}
