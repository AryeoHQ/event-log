<?php

declare(strict_types=1);

namespace Support\Events\Log\Providers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Dispatcher;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_decorates_dispatcher(): void
    {
        $this->assertInstanceOf(Dispatcher::class, app('events'));
    }
}
