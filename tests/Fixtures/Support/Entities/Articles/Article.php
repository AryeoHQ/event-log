<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Support\Entities\Articles\Builder\Builder;
use Tests\Fixtures\Support\Entities\Articles\Collection\Articles;
use Tests\Fixtures\Support\Entities\Articles\Factory\Factory;
use Tests\Fixtures\Support\Entities\Articles\Policy\Policy;

#[CollectedBy(Articles::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Article extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;

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
        'restoring' => Events\Restoring::class,
        'restored' => Events\Restored::class,
        'replicating' => Events\Replicating::class,
        'trashed' => Events\Trashed::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
        'forceDeleting' => Events\ForceDeleting::class,
        'forceDeleted' => Events\ForceDeleted::class,
    ];
}
