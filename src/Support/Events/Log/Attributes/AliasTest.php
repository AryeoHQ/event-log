<?php

declare(strict_types=1);

namespace Support\Events\Log\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(Alias::class)]
class AliasTest extends TestCase
{
    #[Test]
    public function it_stores_the_name(): void
    {
        $attribute = new Alias('post.created');

        $this->assertSame('post.created', $attribute->name);
    }

    #[Test]
    public function it_targets_classes(): void
    {
        $reflection = new ReflectionClass(Alias::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
    }
}
