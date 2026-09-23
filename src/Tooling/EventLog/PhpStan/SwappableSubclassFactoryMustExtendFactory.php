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
final class SwappableSubclassFactoryMustExtendFactory extends Rule
{
    /** @phpstan-ignore missingType.generics */
    private Collection $invalid;

    /**
     * @param  CollectedDataNode  $node
     */
    public function prepare(Node $node, Scope $scope): void
    {
        $this->invalid = SwappableSubclasses::from($node)
            ->filter(fn (array $entry): bool => $entry['factory']['named'] !== null
                && ! is_a($entry['factory']['named'], $entry['factory']['expected'], true));
    }

    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->invalid->isNotEmpty();
    }

    public function handle(Node $node, Scope $scope): void
    {
        $this->invalid->each(fn (array $entry) => $this->errors->push(
            RuleErrorBuilder::message('A '.$entry['model'].' subclass must point #['.class_basename(UseFactory::class).'] at a class extending '.class_basename($entry['factory']['expected']).'.')
                ->file($entry['file'])
                ->line($entry['line'])
                ->identifier('eventLog.Swappable.Subclass.Factory.invalid')
                ->build()
        ));
    }
}
