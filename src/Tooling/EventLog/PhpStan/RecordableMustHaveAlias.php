<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class RecordableMustHaveAlias extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, Recordable::class)
            && $this->doesNotHaveAttribute($node, Alias::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(Recordable::class).' must have a #['.class_basename(Alias::class).'] attribute.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.Recordable.Alias.required',
        );
    }
}
