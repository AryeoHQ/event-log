<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Categories;

use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JsonSerializable;
use Support\Events\Log\Contracts\Loggable;
use Tests\Fixtures\Support\Entities\Articles\Article;

/**
 * @property string $id
 * @property string $name
 */
#[UseResource(JsonResource::class)]
class Category extends Model implements Loggable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function toLoggable(): JsonSerializable
    {
        return $this->toResource();
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory<self>
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return Factory::new();
    }
}
