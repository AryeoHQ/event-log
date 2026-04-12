<?php

declare(strict_types=1);

namespace Tooling\EventLog\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<SupportsEventLogMustUseSerializesModels> */
#[CoversClass(SupportsEventLogMustUseSerializesModels::class)]
class SupportsEventLogMustUseSerializesModelsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SupportsEventLogMustUseSerializesModels;
    }

    #[Test]
    public function it_passes_when_supports_event_log_uses_serializes_models(): void
    {
        $this->analyse([__DIR__.'/../../../Support/Events/Log/Concerns/SupportsEventLog.php'], []);
    }
}
