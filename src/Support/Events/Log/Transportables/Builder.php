<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Events\Log\Transportables\Transportable>
 */
class Builder extends EloquentBuilder
{
    /**
     * @param  class-string<\Support\Events\Log\Transports\Contracts\Transport>|array<int, class-string<\Support\Events\Log\Transports\Contracts\Transport>>  $transports
     */
    final public function transportedByAny(string|array $transports): static
    {
        return $this->where(
            fn (self $query) => collect($transports)->each( // @phpstan-ignore argument.type
                fn (string $transport) => $query->orWhereJsonContains('transports', $transport) // @phpstan-ignore staticMethod.dynamicCall
            )
        );
    }
}
