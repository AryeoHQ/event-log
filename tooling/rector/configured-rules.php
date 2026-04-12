<?php

use Support\Events\Log\Concerns\SupportsEventLog;
use Support\Events\Log\Contracts\Recordable;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tooling\Rector\Rules\AddInterfaceByTrait;
use Tooling\Rector\Rules\AddTraitByInterface;

return [
    AddInterfaceByTrait::class => [
        AsSchema::class => Schema::class,
    ],
    AddTraitByInterface::class => [
        Schema::class => AsSchema::class,
        Recordable::class => SupportsEventLog::class,
    ],
];
