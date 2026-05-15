<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan\Extensions;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tooling\PhpStan\Reflection\Methods\Macro;

#[CoversClass(DisablesSerializesModels::class)]
final class DisablesSerializesModelsTest extends PHPStanTestCase
{
    private DisablesSerializesModels $extension;

    protected function setUp(): void
    {
        $reflectionProvider = self::createReflectionProvider();

        $this->extension = new DisablesSerializesModels($reflectionProvider);
    }

    #[Test]
    public function it_has_method_on_dispatcher(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(Dispatcher::class);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'withoutSerializesModels'));
    }

    #[Test]
    public function it_has_method_on_event_facade(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(Event::class);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'withoutSerializesModels'));
    }

    #[Test]
    public function it_does_not_have_method_on_unrelated_class(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(\stdClass::class);

        $this->assertFalse($this->extension->hasMethod($classReflection, 'withoutSerializesModels'));
    }

    #[Test]
    public function it_does_not_have_nonexistent_method(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(Dispatcher::class);

        $this->assertFalse($this->extension->hasMethod($classReflection, 'nonExistentMethod'));
    }

    #[Test]
    public function it_returns_a_macro_for_dispatcher(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(Dispatcher::class);

        $method = $this->extension->getMethod($classReflection, 'withoutSerializesModels');

        $this->assertInstanceOf(Macro::class, $method);
        $this->assertSame('withoutSerializesModels', $method->getName());
        $this->assertFalse($method->isStatic());
    }

    #[Test]
    public function it_returns_a_static_macro_for_event_facade(): void
    {
        $reflectionProvider = self::createReflectionProvider();
        $classReflection = $reflectionProvider->getClass(Event::class);

        $method = $this->extension->getMethod($classReflection, 'withoutSerializesModels');

        $this->assertInstanceOf(Macro::class, $method);
        $this->assertSame('withoutSerializesModels', $method->getName());
        $this->assertTrue($method->isStatic());
    }
}
