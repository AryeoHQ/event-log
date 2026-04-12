<?php

declare(strict_types=1);

namespace Tooling\EventLog\Rector;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Attributes\Alias;
use Tests\TestCase;
use Tests\Tooling\Concerns\GetsFixtures;
use Tooling\Rector\Rules\Provides\ValidatesAttributes;
use Tooling\Rector\Testing\ParsesNodes;
use Tooling\Rector\Testing\ResolvesRectorRules;

#[CoversClass(RecordableMustHaveAlias::class)]
class RecordableMustHaveAliasTest extends TestCase
{
    use GetsFixtures;
    use ParsesNodes;
    use ResolvesRectorRules;
    use ValidatesAttributes;

    #[Test]
    public function it_adds_alias_attribute_when_missing(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('EventLog/RecordableWithoutAlias.php'));

        $this->assertFalse($this->hasAttribute($classNode, Alias::class));

        $rule = $this->resolveRule(RecordableMustHaveAlias::class);
        $result = $rule->refactor($classNode);

        $this->assertInstanceOf(Class_::class, $result);
        $this->assertTrue($this->hasAttribute($result, Alias::class));
    }

    #[Test]
    public function it_does_not_modify_when_alias_already_present(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('EventLog/ValidRecordableEvent.php'));

        $rule = $this->resolveRule(RecordableMustHaveAlias::class);
        $result = $rule->refactor($classNode);

        $this->assertNull($result);
    }

    #[Test]
    public function it_does_not_modify_when_class_does_not_implement_recordable(): void
    {
        $classNode = $this->getClassNode($this->getFixturePath('EventLog/ClassWithoutSupportsEventLog.php'));

        $rule = $this->resolveRule(RecordableMustHaveAlias::class);
        $result = $rule->refactor($classNode);

        $this->assertNull($result);
    }
}
