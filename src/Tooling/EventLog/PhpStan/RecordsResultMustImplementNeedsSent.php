<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class RecordsResultMustImplementNeedsSent extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, RecordsResult::class)
            && $this->doesNotInherit($node, NeedsSent::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(RecordsResult::class).' must implement the '.class_basename(NeedsSent::class).' interface.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.RecordsResult.NeedsSent.required',
        );
    }
}
