<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Watchdog\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\Deliveries\Delivery;

final class Watchdog extends ConsoleCommand
{
    protected $signature = 'event-log:deliveries:watchdog {--sync : Run the sweep synchronously instead of queueing it}';

    protected $description = 'Fail deliveries stuck in an in-flight status past the grace period.';

    public function handle(): void
    {
        $bite = Delivery::watchdog()->bite();

        $this->option('sync') ? $bite->now() : $bite->dispatch();
    }
}
