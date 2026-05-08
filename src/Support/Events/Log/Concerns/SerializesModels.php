<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Support\Arr;
use Support\Events\Dispatcher\Mixins\DisablesSerializesModels;

trait SerializesModels
{
    use \Illuminate\Queue\SerializesModels {
        getSerializedPropertyValue as private serializesModelsSerializedPropertyValue;
    }

    public private(set) bool $disableSerializesModels = false;

    /**
     * @param  mixed  $value
     * @param  bool  $withRelations
     * @return mixed
     */
    protected function getSerializedPropertyValue($value, $withRelations = true)
    {
        if ($this->disableSerializesModels) {
            return $value;
        }

        $disabled = DisablesSerializesModels::$events;

        when(
            $disabled === true || (is_array($disabled) && Arr::first($disabled, fn (string $class): bool => $this instanceof $class) !== null),
            fn () => $this->disableSerializesModels = true
        );

        return match ($this->disableSerializesModels) {
            true => $value,
            false => $this->serializesModelsSerializedPropertyValue($value, $withRelations),
        };
    }
}
