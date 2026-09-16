<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Recordable;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Data\Variant;
use Tests\Fixtures\Support\Concerns\AnnouncesToLog;
use Tests\Fixtures\Support\Entities\NonRecordable\NonRecordable;
use Tests\Fixtures\Support\Mqtt\PayloadVersion;

/**
 * @property string $id
 * @property string $title
 */
#[UseFactory(Factory::class)]
class Recordable extends Model implements Loggable
{
    use AnnouncesToLog;

    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected $appends = ['preview'];

    protected $dispatchesEvents = [
        'creating' => Events\Creating::class,
        'updated' => Events\Updated::class,
    ];

    public function toLoggable(): Data
    {
        return Data::of(Variant::make($this, version: PayloadVersion::V1));
    }

    /** @return Attribute<string, never> */
    protected function preview(): Attribute // @phpstan-ignore generics.notGeneric
    {
        return Attribute::make(
            get: fn () => str($this->title)->limit(3, '...')->toString()
        );
    }

    /**
     * @return BelongsTo<NonRecordable, $this>
     */
    public function nonRecordable(): BelongsTo
    {
        return $this->belongsTo(NonRecordable::class);
    }
}
