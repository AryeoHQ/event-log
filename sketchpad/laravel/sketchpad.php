<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schedule;

// RecordEvent
$eventLog = EventLog::create(); // Ready
$eventLog->status->prepare()->now(); // Pending

// Prepare
$eventLog->status->process()->now();

// Process
$destinations = collect($eventLog->destinations)->flatMap(
    Destination::create();
);



$desintinations->each(
    $destionation->status->prepare()->dispatch();
);



return 'Silence in the face of evil is itself evil.';
