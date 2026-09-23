<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Events\Log\Transportables\Transportable>
 */
class Builder extends EloquentBuilder {}
