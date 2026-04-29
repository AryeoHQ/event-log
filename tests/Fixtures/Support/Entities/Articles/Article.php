<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Support\Events\Log\Contracts\Loggable;
use Tests\Fixtures\Support\Entities\Categories\Category;

/**
 * @property string $id
 * @property string $title
 * @property string $preview
 */
class Article extends Model implements Loggable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected $appends = ['preview'];

    /**
     * @return array<string, mixed>
     */
    public function toLoggable(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->title,
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function preview(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->title)->limit(3, '...')->toString()
        );
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory<self>
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return Factory::new();
    }
}
