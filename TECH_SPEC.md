### Event Log (NEW \- 0% complete) {#event-log-(new---0%-complete)}

Repository: TBD

This package will provide an opinionated way to log configured custom class-backed events raised in Laravel to the database. It will take a dependency on the [Entity](#entity), [Actions](#actions), and [State Machine](#eloquent-state-machines) packages.

#### Implementation {#implementation-1}

Due to the critical nature of recording events that happen within our applications the package will ship and register a Decorator of Laravel’s Event Dispatcher. This approach allows us to remove this operation from the Listener pipeline – guaranteeing that the result of Listeners registered to a given event can never prevent the event record from being stored while preserving the full feature set & expectations of the framework’s Event / Listener pipeline.

```php
class RecordEvent implements Action, ShouldQueue
{
    use AsAction;

    final public function handle($event)
    {
        if (!($event instanceof Recordable)) {
            return;
        }

        // Validate that context has the expected actor & subject values

        $eventLog = EventLog::create([
            'type' => $event->name,
            'entity' => $event->entity,
            'event' => serialize($event),
            'occurred_at' => now(),
            'actor' => Context::get(config('event_log.key_to_the_actor')),
            'subject' => Context::get(config('event_log.key_to_the_subject')),
        ]);

        $this->prepareDestinations($eventLog);
    }

    private function prepareDestinations(EventLog $eventLog)
    {
        match ($eventLog->event instanceof Destinationable) {
            true => RecordDestination::make($eventLog)->dispatch(),
            false => null
        };
    }
}


class RecordDestination implements Action, ShouldQueue
{
    use AsAction;

    public function handle(EventLog $eventLog)
    {
        $destinationables = $this->destinationables($eventLog);

        $destinationables->each(function (Destinationable $destination) use ($eventLog) {
            $destination = EventLogDestination::create([
                'destination_processor' => '', // lookup from Provider
            ]);

            RecordDelivery::make($destination)->dispatch();
        });
    }

    public function destinationables(EventLog $eventLog): Collection
    {
        // Find all interfaces that extend `Destinationable`
    }
}

class RecordDeliveries implements Action, ShouldQueue
{
    use AsAction;

    public function handle(EventLogDestination $eventLogDestination)
    {
        // Lookup Delivery processor from Provider to create delivery records
        // For each dispatch a ProcessDelivery job
    }
}

class Dispatcher extends \Illuminate\Events\Dispatcher
{
    public function __construct(\Illuminate\Events\Dispatcher $dispatcher)
    {
        collect(get_object_vars($dispatcher))->each(
            fn ($value, $key) => $this->$key = $value
        );
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
	// TODO: Raise an event that a listener can attach to
        RecordEvent::make($event)->dispatch();


        return parent::dispatch($event, $payload, $halt);
    }
}

// Service Provider
app()->extend('events', fn (Dispatcher $original, $app) => new Dispatcher($original));


interface Recordable extends \Support\Entities\Events\Contracts\ForEntity
{
    public string $name { get; }
}

// config/event_log.php
return [
      'key_to_the_actor' => 'actor',
      'key_to_the_subject' => 'subject'
];
```

#### Storage {#storage}

Custom class-backed events recorded will store the serialized `Event` class (which is automatically inclusive of all the public properties of the given Event at the time it is dispatched.) While systems like an audit log or webhooks may need to deliver a payload of the delta this system is not responsible for having an opinion on that need.

30-day pruning strategy

```php
`event_logs`
$table->uuid('id')->primary();
$table->string('type')->index(); // user.updated
$table->string('entity_id')->nullable();
$table->string('entity_type');
$table->longText('event'); // Serialized Event
$table->uuidMorph('actor');
$table->uuidMorph('subject');
$table->timestampTz('occurred_at')->index(); // When the event happened $table->timestampsTz();

$table->index(['entity_type', 'entity_id']);
```

#### Destinations {#destinations}

The event log package will provide a “driver” based implementation that allows recorded events to be “delivered” to custom defined destinations.

##### Implementation {#implementation-2}

A `Destination` interface will be available for extension by downstream consumers allowing them to participate in the destination and deliveries feature of the event log. A `Destinationable` is then applied to an `Event`.

```php
interface Destination extends Recordable {}

interface Webhookable extends Destinationable {}

interface \App\Entities\Users\Events\Created implements Webhookable
```

Each `Destinationable` contract will be expected to register a `Provider` with the event log manager. Supplying implementations of `DestinationProcessor` and `DeliveryProcessor` classes to manage how their `event_log_destinations` and `event_log_destination_deliveries` are handled.

```php
interface DestinationProcessor {}

interface DeliveryProcessor {}

interface Provider
{
    /** @var class-string<DestinationProcessor> */
    public string $destinationProcessor { get; }

    /** @var class-string<DeliveryProcessor> */
    public string $deliveryProcessor { get; }
}

interface WebhookProvider implements Provider { //.. }

Manager::register(Webhookable::class, WebhookProvider::class);
```

##### Storage {#storage-1}

30-day pruning strategy.

For every recorded `Event` that has a destination, one record per `Destinationable` will be created in `event_log_destinations`

```php
`event_log_destinations`
$table->uuid('id')->primary();
$table->uuid('event_log_id'); // UUID from event_logs table. Not constrained. Cleaned up by EventLog::deleted() event
$table->string('destination_processor')->index(): // FQCN extends `DestinationProcessor`
$table->string('status'); // pending, delivered, failed (State Machine)
$table->timestampsTz();
```

For each recorded `EventLogDestination` the `Destinationable`’s `DestinationProcessor` will be responsible for creating the necessary records in `event_log_destination_deliveries`

```php
`event_log_destination_deliveries`
$table->uuid('id')->primary();
$table->uuid('event_log_destination_id')->index(); // UUID from event_log_destinations
$table->jsonb('payload'); // The provider drives the data structure, event log package takes no opinion
$table->string('delivery_processor')->index(); // FQCN extends `DeliveryProcessor`
$table->string('status'); // pending, delivered, failed (StateMachine)
$table->timestampsTz();
```

Each delivery attempted will be recorded in `event_log_destination_delivery_attempts.`

```php
`event_log_destination_delivery_attempts`
$table->uuid('id')->primary();
$table->uuid('event_log_destination_delivery_id')->index();
$table->string('response')->nullable();
$table->string('status'); // pending, delivered, failed (StateMachine)
$table->timestampsTz();
```
