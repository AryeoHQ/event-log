<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class NeedsEnvelopesMustUseCollectsEnvelopes extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, NeedsEnvelopes::class)
            && $this->doesNotInherit($node, CollectsEnvelopes::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(NeedsEnvelopes::class).' must use the '.class_basename(CollectsEnvelopes::class).' trait.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.NeedsEnvelopes.CollectsEnvelopes.required',
        );
    }
}
