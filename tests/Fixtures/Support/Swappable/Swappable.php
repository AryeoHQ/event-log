<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Database\Eloquent\Swappable\Models\Concerns\SupportsSwapping;
use Support\Events\Database\Eloquent\Swappable\Models\Contracts\Swappable as SwappableContract;
use Tests\Fixtures\Support\Swappable\Collection\Swappables;

#[CollectedBy(Swappables::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
class Swappable extends Model implements SwappableContract
{
    /** @use SupportsSwapping<Factory, Builder> */
    use SupportsSwapping;

    final public $incrementing = true;

    final protected $table = 'swappables';

    final protected $primaryKey = 'id';

    final protected $keyType = 'int';

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
        'created' => Events\Created::class,
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = ['name' => 'string'];

    /**
     * @var list<string>
     */
    protected $fillable = ['name'];
}
