<?php

declare(strict_types=1);

namespace Support\Events\Database\Eloquent\Swappable\Models\Contracts;

interface Swappable
{
    /**
     * @param  class-string<static>  $model
     */
    public static function use(string $model): void;

    /**
     * @return class-string<static>
     */
    public static function using(): string;
}
