<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles;

use Illuminate\Database\Eloquent\Relations\Relation;

final class Provider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'article' => Article::class,
        ]);
    }
}
