<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<HasLoggableMustUseSerializesModels> */
#[CoversClass(HasLoggableMustUseSerializesModels::class)]
final class HasLoggableMustUseSerializesModelsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new HasLoggableMustUseSerializesModels;
    }

    #[Test]
    public function it_passes_when_has_loggable_uses_serializes_models(): void
    {
        $this->analyse([__DIR__.'/../../../Support/Events/Log/Provides/HasLoggable.php'], []);
    }
}
