<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use Illuminate\Support\Collection;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Transports\Contracts\Transport;
use Tooling\EventLog\PhpStan\Collectors\TransportAliases;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<CollectedDataNode>
 */
#[NodeType(CollectedDataNode::class)]
final class TransportMustHaveUniqueAlias extends Rule
{
    /** @var Collection<int, array{alias: string, class: string, line: int, file: string}> */
    private Collection $duplicates;

    /**
     * @param  CollectedDataNode  $node
     */
    public function prepare(Node $node, Scope $scope): void
    {
        $this->duplicates = collect($node->get(TransportAliases::class))
            ->flatMap(fn (array $collected, string $file) => collect($collected)->map(
                fn (array $transport): array => [...$transport, 'file' => $file]
            ))
            ->groupBy('alias')
            ->filter(fn (Collection $transports): bool => $transports->count() > 1)
            ->flatten(1);
    }

    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->duplicates->isNotEmpty();
    }

    public function handle(Node $node, Scope $scope): void
    {
        $this->duplicates->each(fn (array $transport) => $this->errors->push(
            RuleErrorBuilder::message(
                class_basename(Transport::class).' #['.class_basename(Alias::class).'] value ['.$transport['alias'].'] must be unique.'
            )
                ->file($transport['file'])
                ->line($transport['line'])
                ->identifier('eventLog.Transport.Alias.name.duplicate')
                ->build()
        ));
    }
}
