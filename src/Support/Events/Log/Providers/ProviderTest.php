<?php

declare(strict_types=1);

namespace Support\Events\Log\Providers;

use Orchestra\Testbench\Attributes\WithEnv;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Events\Dispatcher\Mixins\DisablesSerializesModels;
use Support\Events\Log\Dispatcher\Dispatcher;
use Support\Events\Log\Logs\Log;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_morph_map(): void
    {
        $this->assertSame('event_log', (new Log)->getMorphClass());
    }

    #[Test]
    public function it_registers_config(): void
    {
        $this->assertArrayHasKey('context', config()->get('event_log'));
        $this->assertArrayHasKey('whitelist', config()->get('event_log.context'));
    }

    #[Test]
    public function it_decorates_the_event_dispatcher_when_enabled(): void
    {
        $this->assertInstanceOf(Dispatcher::class, app('events'));
    }

    #[Test]
    #[WithEnv('EVENT_LOG_ENABLED', 'false')]
    public function it_does_not_decorate_the_event_dispatcher_when_disabled(): void
    {
        $this->assertNotInstanceOf(Dispatcher::class, $this->app->make('events'));
    }

    #[Test]
    public function it_registers_disables_serializes_models_mixin(): void
    {
        $reflection = new ReflectionClass(DisablesSerializesModels::class);

        collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
            ->filter(fn (\ReflectionMethod $method) => (string) $method->getReturnType() === \Closure::class)
            ->pluck('name')
            ->each(
                fn (string $method) => $this->assertTrue(\Illuminate\Events\Dispatcher::hasMacro($method)),
            );
    }

    #[Test]
    public function it_loads_migrations(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('event_logs'));
    }
}
