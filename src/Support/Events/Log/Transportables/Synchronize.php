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
            ->whereNotIn('id', $discovery->transportables->pluck('id'))
            ->each(fn (Transportable $stale) => $stale->delete());

        $discovery->transportables->each(fn (array $transport) => $transportable::updateOrCreate(
            ['id' => $transport['id']],
            ['class' => $transport['class'], 'transports' => $transport['transports']],
        ));
    }
}
