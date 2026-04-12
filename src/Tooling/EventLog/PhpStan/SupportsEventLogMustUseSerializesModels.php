<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Concerns\SerializesModels;
use Support\Events\Log\Concerns\SupportsEventLog;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Trait_>
 */
#[NodeType(Trait_::class)]
final class SupportsEventLogMustUseSerializesModels extends Rule
{
    /**
     * @param  Trait_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $node->namespacedName?->toString() === SupportsEventLog::class
            && $this->doesNotInherit($node, SerializesModels::class);
    }

    /**
     * @param  Trait_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            '`SupportsEventLog` must use `SerializesModels`.',
            $node->getStartLine(),
            'eventLog.supportsEventLog.serializesModels',
        );
    }
}
