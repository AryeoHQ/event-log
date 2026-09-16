<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Date;
use Support\Events\Log\Relays\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Events\Log\Relays\Relay>
 */
class Builder extends EloquentBuilder
{
    public function stuck(): self
    {
        return $this->where(function (self $query): void {
            $query->whereIn('status', [Status::Pending, Status::Locked]) // @phpstan-ignore staticMethod.dynamicCall
                ->where('updated_at', '<', Date::now()->toImmutable()->subMinutes((int) config('event_log.watchdog.grace')));
        });
    }
}
