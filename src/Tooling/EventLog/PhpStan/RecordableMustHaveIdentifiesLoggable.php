<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class RecordableMustHaveIdentifiesLoggable extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, Recordable::class)
            && $this->annotatedProperties($node)->isEmpty();
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(Recordable::class).' must have a property annotated with #['.class_basename(IdentifiesLoggable::class).'].',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.Recordable.IdentifiesLoggable.required',
        );
    }

    /**
     * @return \Illuminate\Support\Collection<array-key, Property>
     */
    private function annotatedProperties(Class_ $node): Collection
    {
        return collect($node->stmts)
            ->filter(fn ($stmt) => $stmt instanceof Property && $this->hasAttribute($stmt, IdentifiesLoggable::class));
    }
}
