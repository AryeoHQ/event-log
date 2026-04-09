<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Articles\Builder;

use Support\Database\Eloquent\Contracts\Filterable;
use Support\Database\Eloquent\HasFilters;

/**
 * @template TModel of \Tests\Fixtures\Support\Entities\Articles\Article
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
final class Builder extends \Illuminate\Database\Eloquent\Builder implements Filterable
{
    use HasFilters;
}
