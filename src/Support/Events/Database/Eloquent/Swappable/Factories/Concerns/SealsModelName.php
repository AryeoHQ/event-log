<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Factories\Concerns;

trait SealsModelName
{
    final public function modelName(): string
    {
        return parent::modelName()::using();
    }
}
