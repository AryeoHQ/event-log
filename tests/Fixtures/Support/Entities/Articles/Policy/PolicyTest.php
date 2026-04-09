<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Policy::class)]
final class PolicyTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
