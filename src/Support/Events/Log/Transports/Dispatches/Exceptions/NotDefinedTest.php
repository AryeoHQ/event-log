<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tests\Fixtures\Support\Mqtt\Mqtt;
use Tests\TestCase;

#[CoversClass(NotDefined::class)]
final class NotDefinedTest extends TestCase
{
    #[Test]
    public function it_describes_the_missing_attribute_for_the_transport(): void
    {
        $exception = new NotDefined(Mqtt::class);

        $this->assertSame(
            class_basename(Mqtt::class).' is missing a #['.class_basename(Dispatches::class).'] attribute.',
            $exception->getMessage()
        );
    }
}
