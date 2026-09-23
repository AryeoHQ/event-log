<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Synchronize implements Action
{
    use AsAction;

    public function handle(Discovery $discovery): void
    {
        $transportable = Transportable::using();

        $transportable::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereNotIn('alias', $discovery->transportables->pluck('alias'))
            ->each(fn (Transportable $stale) => $stale->delete());

        $discovery->transportables->each(fn (array $transport) => $transportable::updateOrCreate(
            ['alias' => $transport['alias']],
            ['class' => $transport['class'], 'transports' => $transport['transports']],
        ));
    }
}
