<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Categories\Events;

use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Categories\Category;

#[Alias('category.updated')]
final class Updated implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Category $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }
}
