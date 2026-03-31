<?php

declare(strict_types=1);

namespace Support\Events\Deliveries\Entities;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Deliveries\Builders\Builder;
use Support\Events\Deliveries\Collections\Deliveries;
use Support\Events\Deliveries\Factories\Factory;

/**
 * @use HasFactory<Factory>
 */
#[CollectedBy(Deliveries::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Delivery extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected $table = 'event_log_deliveries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_log_destination_id',
        'payload',
        'delivery_processor',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
