<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

trait SealsSchema
{
    final public function getTable(): string
    {
        return parent::getTable();
    }

    final public function getKeyName(): string
    {
        return parent::getKeyName();
    }
}
