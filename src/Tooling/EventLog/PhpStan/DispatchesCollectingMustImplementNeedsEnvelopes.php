<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Interface_>
 */
#[NodeType(Interface_::class)]
final class DispatchesCollectingMustImplementNeedsEnvelopes extends Rule
{
    /**
     * @param  Interface_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Transport::class)
            && $this->hasAttribute($node, Dispatches::class);
    }

    /**
     * @param  Interface_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $class = $this->resolveArg($node, $scope);

        if ($class === null) {
            return;
        }

        if (! is_a($class, NeedsEnvelopes::class, true)) {
            $this->error(
                message: 'Collecting event ['.class_basename($class).'] must implement '.class_basename(NeedsEnvelopes::class).'.',
                line: $node->name?->getStartLine() ?? $node->getStartLine(),
                identifier: 'eventLog.Dispatches.collecting.invalid',
            );
        }
    }

    private function resolveArg(Interface_ $node, Scope $scope): null|string
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() !== Dispatches::class && $scope->resolveName($attr->name) !== Dispatches::class) {
                    continue;
                }

                foreach ($attr->args as $index => $arg) {
                    $matches = $arg->name?->toString() === 'collecting' || ($arg->name === null && $index === 0);

                    if ($matches && $arg->value instanceof ClassConstFetch && $arg->value->class instanceof Name) {
                        return $scope->resolveName($arg->value->class);
                    }
                }
            }
        }

        return null;
    }
}
