<?php

declare(strict_types=1);

namespace Support\Events\Attempts\Entities;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Attempts\Builders\Builder;
use Support\Events\Attempts\Collections\Attempts;
use Support\Events\Attempts\Factories\Factory;

/**
 * @use HasFactory<Factory>
 */
#[CollectedBy(Attempts::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Attempt extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected $table = 'event_log_delivery_attempts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_log_delivery_id',
        'response',
        'status',
    ];
}
