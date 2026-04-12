<?php

declare(strict_types=1);

namespace Tooling\EventLog\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use Support\Events\Log\Attributes\Alias;
use Support\Events\Log\Contracts\Recordable;
use Tooling\Rector\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class RecordableMustHaveAlias extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node): bool
    {
        return $this->inherits($node, Recordable::class)
            && $this->doesNotHaveAttribute($node, Alias::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node): null|Node
    {
        $className = $node->name?->toString();

        if ($className === null) {
            return null;
        }

        $derivedName = $this->deriveName($className);

        $attribute = new Attribute(
            new FullyQualified(Alias::class),
            [new Arg(new String_($derivedName))],
        );

        array_unshift($node->attrGroups, new AttributeGroup([$attribute]));

        return $node;
    }

    private function deriveName(string $className): string
    {
        $parts = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return '';
        }

        return strtolower(implode('.', $parts));
    }
}
