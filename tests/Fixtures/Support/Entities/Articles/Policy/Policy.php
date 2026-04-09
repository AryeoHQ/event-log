<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Tests\Fixtures\Support\Entities\Articles\Article;

final class Policy
{
    public function view(Authenticatable $user, Article $article): bool
    {
        return true;
    }
}
