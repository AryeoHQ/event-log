<?php

declare(strict_types=1);

namespace Support\Events\Log\Attributes\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

#[CoversClass(AliasMissing::class)]
class AliasMissingTest extends TestCase
{
    #[Test]
    public function it_describes_the_missing_attribute(): void
    {
        $exception = AliasMissing::on('App\Events\Created');

        $this->assertSame(
            'The [App\Events\Created] event must have the #[Alias] attribute.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function it_is_a_logic_exception(): void
    {
        $this->assertInstanceOf(RuntimeException::class, AliasMissing::on('Foo'));
    }
}
