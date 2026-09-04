<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Provides\HasRelays;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class TransportMustUseHasRelays extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, Transport::class)
            && $this->doesNotInherit($node, HasRelays::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'A class that implements an interface extending '.class_basename(Transport::class).' must use the '.class_basename(HasRelays::class).' trait.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.Transport.HasRelays.required',
        );
    }
}
