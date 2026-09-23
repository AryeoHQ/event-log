<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Database\Seeders;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transportables\Transportable;
use Tests\Fixtures\Support\Entities\Transportables\Transportable as ExtendedTransportable;
use Tests\TestCase;

#[CoversClass(Sync::class)]
final class SyncTest extends TestCase
{
    #[Test]
    public function it_fills_the_catalog(): void
    {
        $this->seed(Sync::class);

        $this->assertNotSame(0, Transportable::count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function it_runs_without_queueing(): void
    {
        Queue::fake();

        $this->seed(Sync::class);

        Queue::assertNothingPushed();
        $this->assertNotSame(0, Transportable::count()); // @phpstan-ignore staticMethod.dynamicCall
    }

    #[Test]
    public function it_seeds_the_model_given_to_use(): void
    {
        Transportable::use(ExtendedTransportable::class);

        $this->seed(Sync::class);

        $this->assertInstanceOf(ExtendedTransportable::class, Transportable::using()::query()->first());
    }

    #[Test]
    public function it_can_run_repeatedly(): void
    {
        $this->seed(Sync::class);

        $seeded = Transportable::count(); // @phpstan-ignore staticMethod.dynamicCall

        $this->seed(Sync::class);

        $this->assertSame($seeded, Transportable::count()); // @phpstan-ignore staticMethod.dynamicCall
    }
}
