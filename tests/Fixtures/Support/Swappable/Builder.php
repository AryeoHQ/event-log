<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Tests\Fixtures\Support\Swappable\Swappable>
 */
class Builder extends EloquentBuilder {}
