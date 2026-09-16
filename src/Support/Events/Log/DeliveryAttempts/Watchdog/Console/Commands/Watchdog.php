<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Watchdog\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;

final class Watchdog extends ConsoleCommand
{
    protected $signature = 'event-log:delivery-attempts:watchdog {--sync : Run the sweep synchronously instead of queueing it}';

    protected $description = 'Fail delivery attempts stuck in an in-flight status past the grace period.';

    public function handle(): void
    {
        $bite = DeliveryAttempt::watchdog()->bite();

        $this->option('sync') ? $bite->now() : $bite->dispatch();
    }
}
