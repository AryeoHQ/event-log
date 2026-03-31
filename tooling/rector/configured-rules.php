<?php

use Support\Http\Resources\Schemas\Contracts\Schema;
use Tooling\Rector\Rules\AddInterfaceByTrait;
use Tooling\Rector\Rules\AddTraitByInterface;
use Support\Http\Resources\Schemas\Provides\AsSchema;

return [
    AddInterfaceByTrait::class => [
        AsSchema::class => Schema::class,
    ],
    AddTraitByInterface::class => [
        Schema::class => AsSchema::class,
    ],
];
