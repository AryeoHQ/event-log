<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

use Illuminate\Support\Facades\Facade;
use Support\Events\Database\Eloquent\Swappable\Swapper\Facades\Swapper;

trait InheritsDeclarations
{
    final protected function initializeTraits(): void
    {
        parent::initializeTraits();

        // Guarded until `AddStateMachineablePropertiesToModelDocBlocks:L513` stops calling new $class()->getCasts()
        if (Facade::getFacadeApplication()) {
            Swapper::consolidate($this);
        }
    }
}
