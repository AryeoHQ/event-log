<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan\Collectors;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\PhpStan\Rules\Provides\ValidatesInheritance;

/**
 * @implements Collector<Class_, array{alias: string, class: string, line: int}>
 */
final class TransportAliases implements Collector
{
    use ValidatesInheritance;

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param  Class_  $node
     * @return null|array{alias: string, class: string, line: int}
     */
    public function processNode(Node $node, Scope $scope)
    {
        if ($node->isAnonymous() || ! $this->inherits($node, Transport::class)) {
            return null;
        }

        $alias = $this->resolveAlias($node, $scope);

        return match ($alias) {
            null => null,
            default => [
                'alias' => $alias,
                'class' => (string) $node->namespacedName,
                'line' => $node->name?->getStartLine() ?? $node->getStartLine(),
            ],
        };
    }

    private function resolveAlias(Class_ $node, Scope $scope): null|string
    {
        return collect($node->attrGroups)
            ->flatMap(fn (AttributeGroup $group): array => $group->attrs)
            ->filter(fn (Attribute $attribute): bool => $attribute->name->toString() === Alias::class
                || $scope->resolveName($attribute->name) === Alias::class)
            ->flatMap(fn (Attribute $attribute): array => $attribute->args)
            ->filter(fn (Arg $argument, int $index): bool => $argument->name?->toString() === 'name' || $index === 0)
            ->map(fn (Arg $argument): Expr => $argument->value)
            ->whereInstanceOf(String_::class)
            ->first()?->value;
    }
}
