<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Concerns;

trait SealsInstance
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    final public function newInstance($attributes = [], $exists = false)
    {
        $model = new (static::using());

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        $model->mergeCasts($this->casts);

        $model->fill((array) $attributes);

        return $model;
    }
}
