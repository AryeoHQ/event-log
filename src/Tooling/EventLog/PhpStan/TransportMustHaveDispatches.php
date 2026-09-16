<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Interface_>
 */
#[NodeType(Interface_::class)]
final class TransportMustHaveDispatches extends Rule
{
    /**
     * @param  Interface_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Transport::class)
            && $this->doesNotHaveAttribute($node, Dispatches::class);
    }

    /**
     * @param  Interface_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: class_basename(Transport::class).' must have a #['.class_basename(Dispatches::class).'] attribute.',
            line: $node->name?->getStartLine() ?? $node->getStartLine(),
            identifier: 'eventLog.Relayable.Dispatches.required',
        );
    }
}
