<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Concerns\SerializesModels;
use Support\Events\Log\Provides\HasLoggable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Trait_>
 */
#[NodeType(Trait_::class)]
final class HasLoggableMustUseSerializesModels extends Rule
{
    /**
     * @param  Trait_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $node->namespacedName?->toString() === HasLoggable::class
            && $this->doesNotInherit($node, SerializesModels::class);
    }

    /**
     * @param  Trait_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(HasLoggable::class).' must use the '.class_basename(SerializesModels::class).' trait.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.HasLoggable.SerializesModels.required',
        );
    }
}
