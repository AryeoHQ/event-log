<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan\Extensions;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use Support\Events\Dispatcher\Mixins;
use Tooling\PhpStan\Reflection\Classes\Mixin;
use Tooling\PhpStan\Reflection\Methods\Macro;

class DisablesSerializesModels implements MethodsClassReflectionExtension
{
    private Mixin $mixin;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->mixin = new Mixin($reflectionProvider, Mixins\DisablesSerializesModels::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Dispatcher::class) && ! $classReflection->is(Event::class)) {
            return false;
        }

        return $this->mixin->hasMethod($classReflection, $methodName, static: $classReflection->is(Event::class));
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $method = $this->mixin->getMethod($classReflection, $methodName, static: $classReflection->is(Event::class));

        if (! $method instanceof Macro) {
            throw new ShouldNotHappenException;
        }

        return $method;
    }
}
