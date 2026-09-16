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
final class HasRelaysMustImplementTransport extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, HasRelays::class)
            && $this->doesNotInherit($node, Transport::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(HasRelays::class).' must implement an interface that extends '.class_basename(Transport::class).'.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.HasRelays.Transport.required',
        );
    }
}
