<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Factory::class)]
final class FactoryTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
