<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Article::class)]
final class UserTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
