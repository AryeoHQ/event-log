<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan\Collectors;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Tooling\PhpStan\Rules\Provides\ValidatesInheritance;

/**
 * @implements Collector<Class_, array{model: string, line: int, factory: array{named: ?string, expected: string}, builder: array{named: ?string, expected: string}, collection: array{named: ?string, expected: string}}>
 */
final class SwappableSubclasses implements Collector
{
    use ValidatesInheritance;

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param  Class_  $node
     * @return null|array{model: string, line: int, factory: array{named: ?string, expected: string}, builder: array{named: ?string, expected: string}, collection: array{named: ?string, expected: string}}
     */
    public function processNode(Node $node, Scope $scope)
    {
        if ($node->isAnonymous() || ! $this->inherits($node, Swappable::class)) {
            return null;
        }

        $base = $this->base($node);

        if ($base === null) {
            return null;
        }

        $native = $base->getNativeReflection();

        return [
            'model' => class_basename($base->getName()),
            'line' => $node->name?->getStartLine() ?? $node->getStartLine(),
            'factory' => [
                'named' => $this->attributeArgument($node, UseFactory::class, $scope),
                'expected' => $native->getAttributes(UseFactory::class)[0]->getArguments()[0],
            ],
            'builder' => [
                'named' => $this->attributeArgument($node, UseEloquentBuilder::class, $scope),
                'expected' => $native->getAttributes(UseEloquentBuilder::class)[0]->getArguments()[0],
            ],
            'collection' => [
                'named' => $this->attributeArgument($node, CollectedBy::class, $scope),
                'expected' => $native->getAttributes(CollectedBy::class)[0]->getArguments()[0],
            ],
        ];
    }

    private function base(Class_ $node): null|ClassReflection
    {
        $class = $node->namespacedName?->toString();

        if ($class === null || ! $this->reflectionProvider->hasClass($class)) {
            return null;
        }

        return collect($this->reflectionProvider->getClass($class)->getAncestors())
            ->last(fn (ClassReflection $ancestor) => $ancestor->isClass()
                && $ancestor->implementsInterface(Swappable::class)
                && $ancestor->getName() !== $class);
    }

    /**
     * @param  class-string  $attribute
     */
    private function attributeArgument(Class_ $node, string $attribute, Scope $scope): null|string
    {
        return collect($node->attrGroups)
            ->flatMap(fn ($group) => $group->attrs)
            ->filter(fn ($attr) => $scope->resolveName($attr->name) === $attribute)
            ->flatMap(fn ($attr) => $attr->args)
            ->map(fn ($arg) => $arg->value)
            ->whereInstanceOf(ClassConstFetch::class)
            ->filter(fn (ClassConstFetch $fetch) => $fetch->class instanceof Name)
            ->map(fn (ClassConstFetch $fetch) => $scope->resolveName($fetch->class))
            ->first();
    }

    /** @phpstan-ignore missingType.generics */
    public static function from(CollectedDataNode $node): Collection
    {
        return collect($node->get(self::class))
            ->flatMap(fn (array $entries, string $file) => array_map(
                fn (array $entry): array => [...$entry, 'file' => $file],
                $entries,
            ));
    }
}
