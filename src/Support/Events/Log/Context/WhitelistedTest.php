<?php

declare(strict_types=1);

namespace Support\Events\Log\Context;

use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Testbench\Attributes\WithEnv;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Tests\Fixtures\Support\Entities\Articles\Article;
use Tests\Fixtures\Support\Entities\Articles\Events\Updated;
use Tests\TestCase;

#[CoversClass(Whitelisted::class)]
final class WhitelistedTest extends TestCase
{
    #[Test]
    public function it_uses_current_context_when_none_provided(): void
    {
        Context::add('tenant', 'acme');

        $whitelisted = new Whitelisted;

        $this->assertSame('acme', $whitelisted->context->get('tenant'));
    }

    #[Test]
    public function it_accepts_an_explicit_repository(): void
    {
        $repository = app(Repository::class)->add(['foo' => 'bar']);

        $whitelisted = new Whitelisted($repository);

        $this->assertSame($repository, $whitelisted->context);
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant,request_id')]
    public function it_filters_to_array_by_whitelist(): void
    {
        Context::add('tenant', 'acme');
        Context::add('request_id', 'req-123');
        Context::add('secret', 'should-not-appear');

        $whitelisted = new Whitelisted;

        $this->assertSame(['tenant' => 'acme', 'request_id' => 'req-123'], $whitelisted->toArray());
        $this->assertArrayNotHasKey('secret', $whitelisted->toArray());
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant')]
    public function it_gets_whitelisted_key(): void
    {
        Context::add('tenant', 'acme');

        $whitelisted = new Whitelisted;

        $this->assertSame('acme', $whitelisted->get('tenant'));
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant')]
    public function it_returns_null_for_non_whitelisted_key(): void
    {
        Context::add('secret', 'hidden');

        $whitelisted = new Whitelisted;

        $this->assertNull($whitelisted->get('secret'));
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant')]
    public function it_json_serializes_using_whitelist(): void
    {
        Context::add('tenant', 'acme');
        Context::add('extra', 'ignored');

        $whitelisted = new Whitelisted;

        $this->assertSame(['tenant' => 'acme'], $whitelisted->jsonSerialize());
    }

    #[Test]
    public function it_returns_empty_array_when_whitelist_is_empty(): void
    {
        Context::add('tenant', 'acme');

        $whitelisted = new Whitelisted;

        $this->assertSame([], $whitelisted->toArray());
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant')]
    public function it_casts_to_json_when_set_on_model(): void
    {
        Context::add('tenant', 'acme');

        $log = Log::make()->forceFill([
            'context' => new Whitelisted,
        ]);

        $this->assertIsString($log->getAttributes()['context']);
        $this->assertStringContainsString('tenant', $log->getAttributes()['context']);
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'foo')]
    public function it_casts_repository_to_whitelisted_when_set_on_model(): void
    {
        $repository = app(Repository::class)->add(['foo' => 'bar']);

        $log = Log::make()->forceFill([
            'context' => $repository,
        ]);

        $this->assertIsString($log->getAttributes()['context']);
        $this->assertStringContainsString('foo', $log->getAttributes()['context']);
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant')]
    public function it_casts_null_to_current_context_when_set_on_model(): void
    {
        Context::add('tenant', 'acme');

        $log = Log::make()->forceFill([
            'context' => null,
        ]);

        $this->assertIsString($log->getAttributes()['context']);
        $this->assertStringContainsString('tenant', $log->getAttributes()['context']);
    }

    #[Test]
    public function it_throws_when_set_with_unsupported_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Log::make()->forceFill([
            'context' => 'not-valid',
        ]);
    }

    #[Test]
    public function it_casts_from_json_when_retrieved_from_model(): void
    {
        $log = Log::make();
        $log->setRawAttributes(['context' => json_encode(['tenant' => 'acme'])]);

        $this->assertInstanceOf(Whitelisted::class, $log->context);
        $this->assertSame('acme', $log->context->context->get('tenant'));
    }

    #[Test]
    #[WithEnv('EVENT_LOG_CONTEXT_WHITELIST', 'tenant_id,request_id')]
    public function it_hydrates_an_isolated_context_from_the_database(): void
    {
        Context::add('tenant_id', '123');

        $log = Log::create([
            'event' => new Updated(Article::factory()->create()),
            'context' => Context::getFacadeRoot(),
            'idempotency_key' => Str::uuid7()->toString(),
            'occurred_at' => now(),
        ]);

        Context::flush();
        Context::add('request_id', '456');

        $hydrated = $log->fresh()->context;

        $this->assertSame('123', $hydrated->get('tenant_id'));
        $this->assertNull($hydrated->get('request_id'));
    }

    #[Test]
    public function it_casts_null_from_database_as_null(): void
    {
        $log = Log::make();
        $log->setRawAttributes(['context' => null]);

        $this->assertNull($log->context);
    }
}
