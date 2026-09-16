<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Watchdog\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\Relays\Relay;

final class Watchdog extends ConsoleCommand
{
    protected $signature = 'event-log:relays:watchdog {--sync : Run the sweep synchronously instead of queueing it}';

    protected $description = 'Fail relays stuck in an in-flight status past the grace period.';

    public function handle(): void
    {
        $bite = Relay::watchdog()->bite();

        $this->option('sync') ? $bite->now() : $bite->dispatch();
    }
}
