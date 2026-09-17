<?php

declare(strict_types=1);

use Tooling\EloquentStateMachines\Rector\Rules\AddStateMachineablePropertiesToModelDocBlocks;

return [
    // The rule instantiates the model to read its casts, which boots Sushi and calls
    // storage_path() — unavailable in Rector's bare container.
    AddStateMachineablePropertiesToModelDocBlocks::class => [
        __DIR__.'/../../src/Support/Events/Transportables/Transportable.php',
    ],
];
