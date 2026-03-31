<?php

declare(strict_types=1);

namespace Support\Events\Destinations\Entities;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Destinations\Builders\Builder;
use Support\Events\Destinations\Collections\Destinations;
use Support\Events\Destinations\Factories\Factory;

/**
 * @use HasFactory<Factory>
 */
#[CollectedBy(Destinations::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Destination extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected $table = 'event_log_destinations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_log_id',
        'destination_processor',
        'status',
    ];
}
