<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Comments;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;

/**
 * @property string $id
 * @property string $body
 */
class Comment extends Model implements Loggable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function toLoggable(): Jsonable
    {
        return new JsonPayload([
            'id' => $this->getKey(),
            'body' => $this->body,
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
