<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Console\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Support\Events\Log\Transportables\Synchronize as SynchronizeTransportables;
use Symfony\Component\Console\Input\InputOption;

final class Synchronize extends ConsoleCommand
{
    protected $name = 'event-log:transportables:synchronize';

    protected $description = 'Synchronize the catalog of events that implement a transport.';

    /** @return list<InputOption> */
    protected function getOptions(): array
    {
        return [
            new InputOption('sync', null, InputOption::VALUE_NONE, 'Run the synchronization synchronously instead of queueing it'),
            new InputOption('prune', null, InputOption::VALUE_NONE, 'Delete rows for transports that are no longer discovered'),
        ];
    }

    public function handle(): void
    {
        $synchronize = SynchronizeTransportables::make((bool) $this->option('prune'));

        $this->option('sync') ? $synchronize->now() : $synchronize->dispatch();
    }
}
