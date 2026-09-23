<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\Transportables\Synchronize as SynchronizeTransportables;

final class Synchronize extends ConsoleCommand
{
    protected $signature = 'event-log:transportables:synchronize {--sync : Run the synchronization synchronously instead of queueing it}';

    protected $description = 'Synchronize the catalog of events that implement a transport.';

    public function handle(): void
    {
        $synchronize = SynchronizeTransportables::make();

        $this->option('sync') ? $synchronize->now() : $synchronize->dispatch();
    }
}
