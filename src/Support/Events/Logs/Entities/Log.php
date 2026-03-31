<?php

declare(strict_types=1);

namespace Support\Events\Logs\Entities;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Logs\Builders\Builder;
use Support\Events\Logs\Collections\Logs;
use Support\Events\Logs\Factories\Factory;

/**
 * @use HasFactory<Factory>
 */
#[CollectedBy(Logs::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Log extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected $table = 'event_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'entity_id',
        'entity_type',
        'event',
        'actor_id',
        'actor_type',
        'subject_id',
        'subject_type',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
