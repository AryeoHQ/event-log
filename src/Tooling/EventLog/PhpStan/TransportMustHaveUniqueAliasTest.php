<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Transports\Contracts\Transport;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\EventLog\PhpStan\Collectors\TransportAliases;

/** @extends RuleTestCase<TransportMustHaveUniqueAlias> */
#[CoversClass(TransportMustHaveUniqueAlias::class)]
#[CoversClass(TransportAliases::class)]
final class TransportMustHaveUniqueAliasTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new TransportMustHaveUniqueAlias;
    }

    /**
     * @return array<int, Collector<\PhpParser\Node\Stmt\Class_, array{alias: string, class: string, line: int}>>
     */
    protected function getCollectors(): array
    {
        return [new TransportAliases];
    }

    #[Test]
    public function it_passes_when_aliases_are_unique(): void
    {
        $this->analyse([
            $this->getFixturePath('EventLog/TransportWithoutHasRelays.php'),
            $this->getFixturePath('../Support/Entities/Relayable/Events/Updated.php'),
        ], []);
    }

    #[Test]
    public function it_passes_when_a_class_does_not_implement_transport(): void
    {
        $this->analyse([$this->getFixturePath('EventLog/ClassNotRecordable.php')], []);
    }

    #[Test]
    public function it_fails_when_two_transports_share_an_alias(): void
    {
        $message = class_basename(Transport::class).' #['.class_basename(Alias::class).'] value [test.transport] must be unique.';

        $this->analyse([
            $this->getFixturePath('EventLog/TransportWithDuplicateAlias.php'),
            $this->getFixturePath('EventLog/TransportWithoutHasRelays.php'),
        ], [
            [$message, 12],
            [$message, 12],
        ]);
    }
}
