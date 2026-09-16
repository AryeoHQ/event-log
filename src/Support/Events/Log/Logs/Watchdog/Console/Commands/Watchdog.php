<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Watchdog\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\Logs\Log;

final class Watchdog extends ConsoleCommand
{
    protected $signature = 'event-log:logs:watchdog {--sync : Run the sweep synchronously instead of queueing it}';

    protected $description = 'Fail logs stuck in an in-flight status past the grace period.';

    public function handle(): void
    {
        $bite = Log::watchdog()->bite();

        $this->option('sync') ? $bite->now() : $bite->dispatch();
    }
}
