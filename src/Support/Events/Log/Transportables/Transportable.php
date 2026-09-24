<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable;
use Support\Events\Log\Transportables\Collection\Transportables;

/**
 * @property string $id
 * @property class-string<\Support\Events\Log\Transports\Contracts\Transport> $class
 * @property \Illuminate\Support\Collection<int, class-string<\Support\Events\Log\Transports\Contracts\Transport>> $transports
 */
#[CollectedBy(Transportables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Transportable extends Model implements Swappable
{
    /** @use SupportsSwapping<Factory, Builder> */
    use SupportsSwapping;

    final public $incrementing = false;

    final protected $table = 'event_log_transportables';

    final protected $primaryKey = 'id';

    final protected $keyType = 'string';

    final public function getKeyType(): string
    {
        return parent::getKeyType();
    }

    final public function getIncrementing(): bool
    {
        return parent::getIncrementing();
    }

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'retrieved' => Events\Retrieved::class,
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        'updating' => Events\Updating::class,
        'updated' => Events\Updated::class,
        'saving' => Events\Saving::class,
        'saved' => Events\Saved::class,
        'replicating' => Events\Replicating::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
    ];

    protected $fillable = [
        'id',
        'class',
        'transports',
    ];

    protected $casts = [
        'transports' => AsCollection::class,
    ];
}
