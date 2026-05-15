<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Contracts\Recordable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class RecordableMustNotOverrideSerialization extends Rule
{
    private const array METHODS = [
        '__sleep',
        '__wakeup',
        '__serialize',
        '__unserialize',
    ];

    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return ! $node->isAnonymous()
            && $this->inherits($node, Recordable::class)
            && $this->definesAnySerializationMethod($node) !== [];
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        foreach ($this->definesAnySerializationMethod($node) as $method) {
            $this->error(
                message: class_basename(Recordable::class)." must not override {$method}().",
                line: $node->name?->getStartLine() ?? $node->getStartLine(),
                identifier: 'eventLog.Recordable.SerializationMethods.forbidden',
            );
        }
    }

    /**
     * @return list<string>
     */
    private function definesAnySerializationMethod(Class_ $node): array
    {
        $found = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod
                && in_array($stmt->name->toString(), self::METHODS, true)
                && $stmt->stmts !== null) {
                $found[] = $stmt->name->toString();
            }
        }

        return $found;
    }
}
