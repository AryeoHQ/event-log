<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;

/**
 * @property string $id
 * @property string $title
 */
class Tag extends Model implements Loggable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    /**
     * @return Arrayable<string, mixed>
     */
    public function toLoggable(): Arrayable
    {
        return collect([
            'id' => $this->getKey(),
            'title' => $this->title,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory<self>
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return Factory::new();
    }
}
