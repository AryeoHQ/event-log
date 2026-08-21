<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Interface_>
 */
#[NodeType(Interface_::class)]
final class DispatchesSendingMustImplementNeedsSent extends Rule
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
        $class = $this->resolveNamedArg($node, 'sending', $scope);

        if ($class === null) {
            return;
        }

        if (! is_a($class, NeedsSent::class, true)) {
            $this->error(
                message: 'Sending event ['.class_basename($class).'] must implement '.class_basename(NeedsSent::class).'.',
                line: $node->name?->getStartLine() ?? $node->getStartLine(),
                identifier: 'eventLog.Dispatches.sending.invalid',
            );
        }
    }

    private function resolveNamedArg(Interface_ $node, string $name, Scope $scope): null|string
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() !== Dispatches::class && $scope->resolveName($attr->name) !== Dispatches::class) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if ($arg->name?->toString() === $name && $arg->value instanceof ClassConstFetch && $arg->value->class instanceof Name) {
                        return $scope->resolveName($arg->value->class);
                    }
                }
            }
        }

        return null;
    }
}
