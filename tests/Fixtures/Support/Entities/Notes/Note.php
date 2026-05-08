<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Notes;

use Generator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;

/**
 * @property string $id
 * @property string $content
 */
class Note extends Model implements Loggable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    /**
     * @return Generator<string, mixed>
     */
    public function toLoggable(): Generator
    {
        yield 'id' => $this->getKey();
        yield 'content' => $this->content;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory<self>
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return Factory::new();
    }
}
