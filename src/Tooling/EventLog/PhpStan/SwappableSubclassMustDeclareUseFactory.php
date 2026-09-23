<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Support\Collection;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use Tooling\EventLog\PhpStan\Collectors\SwappableSubclasses;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<CollectedDataNode>
 */
#[NodeType(CollectedDataNode::class)]
final class SwappableSubclassMustDeclareUseFactory extends Rule
{
    /** @phpstan-ignore missingType.generics */
    private Collection $missing;

    /**
     * @param  CollectedDataNode  $node
     */
    public function prepare(Node $node, Scope $scope): void
    {
        $this->missing = SwappableSubclasses::from($node)
            ->filter(fn (array $entry): bool => $entry['factory']['named'] === null);
    }

    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->missing->isNotEmpty();
    }

    public function handle(Node $node, Scope $scope): void
    {
        $this->missing->each(fn (array $entry) => $this->errors->push(
            RuleErrorBuilder::message('A '.$entry['model'].' subclass must declare #['.class_basename(UseFactory::class).'].')
                ->file($entry['file'])
                ->line($entry['line'])
                ->identifier('eventLog.Swappable.Subclass.UseFactory.required')
                ->build()
        ));
    }
}
