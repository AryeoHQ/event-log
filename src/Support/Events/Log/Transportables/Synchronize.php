<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Synchronize implements Action
{
    use AsAction;

    public readonly bool $shouldPrune;

    /** @var class-string<Transportable> */
    private string $transportable {
        get => Transportable::using();
    }

    public function __construct(bool $shouldPrune = false)
    {
        $this->shouldPrune = $shouldPrune;
    }

    public function handle(Discovery $discovery): void
    {
        $this->prune($discovery)->record($discovery);
    }

    private function record(Discovery $discovery): self
    {
        $discovery->transportables->each(fn (array $transport) => $this->transportable::updateOrCreate(
            ['id' => $transport['id']],
            ['class' => $transport['class'], 'transports' => $transport['transports']],
        ));

        return $this;
    }

    private function prune(Discovery $discovery): self
    {
        if (! $this->shouldPrune) {
            return $this;
        }

        $this->transportable::query() // @phpstan-ignore staticMethod.dynamicCall
            ->whereNotIn('id', $discovery->transportables->pluck('id'))
            ->each(fn (Transportable $stale) => $stale->delete());

        return $this;
    }
}
