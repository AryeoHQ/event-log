<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class IdentifiesLoggableMustBeLoggableModel extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, Recordable::class)
            && $this->findAnnotatedProperty($node) !== null;
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $property = $this->findAnnotatedProperty($node);

        if ($property === null) {
            return;
        }

        $type = $property->type;

        $resolvedTypes = match (true) {
            $type instanceof IntersectionType => collect($type->types)
                ->filter(fn (Node $t): bool => $t instanceof Name)
                ->map(fn (Name $t): string => $scope->resolveName($t))
                ->all(),
            $type instanceof Name => [$scope->resolveName($type)],
            default => [],
        };

        if (! $this->satisfies($resolvedTypes, Model::class) || ! $this->satisfies($resolvedTypes, Loggable::class)) {
            $this->error(
                message: 'The #['.class_basename(IdentifiesLoggable::class).'] property must be typed as '.class_basename(Model::class).' & '.class_basename(Loggable::class).'.',
                line: $property->getStartLine(),
                identifier: 'eventLog.Recordable.IdentifiesLoggable.type',
            );
        }
    }

    private function findAnnotatedProperty(Class_ $node): null|Property
    {
        return collect($node->stmts)
            ->filter(fn ($stmt) => $stmt instanceof Property)
            ->filter(fn (Property $stmt) => $this->hasAttribute($stmt, IdentifiesLoggable::class))
            ->first();
    }

    /**
     * @param  iterable<array-key, string>  $resolvedTypes
     * @param  class-string  $contract
     */
    private function satisfies(iterable $resolvedTypes, string $contract): bool
    {
        return collect($resolvedTypes)->contains(
            fn (string $resolved) => $resolved === $contract || is_subclass_of($resolved, $contract, true)
        );
    }
}
