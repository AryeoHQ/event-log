# Architecture

This document describes the models, the tables, and the recording path. For the
cascade that drives them, see [lifecycle.md](lifecycle.md). For the state
machines, see [state-machines.md](state-machines.md). For a top-down overview,
start at [README.md](README.md).

The namespace root is `Support\Events\Log\`, under `src/Support/Events/Log/`.

## The four-tier pipeline

A recorded event moves through four models. Each model has a UUID key and its own
state-machine `status` column. Each tier creates the tier below it.

```
Event dispatched
      │
      ▼
   Log ──────────▶ per transport ──▶ Relay
                                       │
                                       ▼
                              per envelope ──▶ Delivery
                                                  │
                                                  ▼
                                        per attempt ──▶ DeliveryAttempt
```

| Tier | Model | Table | Status states |
|---|---|---|---|
| Log | `Logs\Log` | `event_logs` | Pending, Locked, Processed, Failed, Compromised |
| Relay | `Relays\Relay` | `event_log_relays` | Pending, Locked, Processed, Failed |
| Delivery | `Deliveries\Delivery` | `event_log_deliveries` | Pending, Locked, Succeeded, Failed, Undeliverable |
| DeliveryAttempt | `DeliveryAttempts\DeliveryAttempt` | `event_log_delivery_attempts` | Pending, Locked, Succeeded, Failed, Undeliverable |

## The tables

### `event_logs`

| Column | Type | Meaning |
|---|---|---|
| `id` | uuid PK | Primary key |
| `idempotency_key` | uuid unique | Dedup key (`LogEvent::$uniqueId`, a UUIDv7) |
| `type` | string | The `#[Alias]` string (covered by composite indexes) |
| `status` | string | State-machine status |
| `context` | json | Whitelisted `Context` snapshot |
| `data` | json nullable | `toLoggable()` output, keyed by version value |
| `event` | binary | HMAC-signed, base64, serialized event object |
| `loggable_id` / `loggable_type` | uuid nullable / string nullable | Polymorphic reference to the `Loggable` model (declared by hand for a wider composite index) |
| `occurred_at` | timestampTz | Captured at dispatch time (covered by composite indexes) |
| `created_at` / `updated_at` | timestampsTz | Row timestamps |

### `event_log_relays`

| Column | Type | Meaning |
|---|---|---|
| `id` | uuid PK | Primary key |
| `event_log_id` | uuid indexed | Foreign key to `event_logs` |
| `transport` | string | Class-string of the `Transport` sub-interface |
| `status` | string | State-machine status |
| `created_at` / `updated_at` | timestampsTz | Row timestamps |

### `event_log_deliveries`

| Column | Type | Meaning |
|---|---|---|
| `id` | uuid PK | Primary key |
| `event_log_relay_id` | uuid indexed | Foreign key to `event_log_relays` |
| `recipient_id` / `recipient_type` | hand-declared, **NOT NULL** | Polymorphic reference to the recipient |
| `version` | string nullable | Requested payload version (null means the full map) |
| `tries` | unsignedSmallInteger | Attempt budget (seeded from `#[Tries]`) |
| `status` | string | State-machine status |
| `created_at` / `updated_at` | timestampsTz | Row timestamps |

The recipient columns are declared by hand, not by `uuidMorphs`. They are NOT
NULL. A delivery always has a recipient.

### `event_log_delivery_attempts`

| Column | Type | Meaning |
|---|---|---|
| `id` | uuid PK | Primary key |
| `event_log_delivery_id` | uuid indexed | Foreign key to `event_log_deliveries` |
| `status` | string | State-machine status |
| `response` | text nullable | Result or error recorded from the send |
| `attempted_at` | timestampTz nullable | When the attempt ran |
| `created_at` / `updated_at` | timestampsTz | Row timestamps |

An attempt sets `$touches = ['delivery']`. So each new attempt updates the parent
delivery's `updated_at`. This keeps the parent's timestamp fresh while the
delivery retries. The watchdog depends on this (see [The watchdog](#the-watchdog)).

## Recording an event

### The dispatcher decorator

`Dispatcher\Dispatcher` is a final decorator. It implements
`Illuminate\Contracts\Events\Dispatcher` and wraps the framework dispatcher in a
`private readonly $decorated`. It overrides only `dispatch()` and `until()`. Both
call `record($event)` before they delegate, so the log row is written before any
listener runs. The `Dispatcher\Concerns\ForwardsCalls` trait forwards every other
method without change.

```php
private function record(string|object $event): void
{
    rescue(
        fn () => LogEvent::make($event)->dispatchAfterFailed()->now(),
        report: fn (\Throwable $exception) => report(RecordingFailed::from($exception)),
    );
}
```

The `report:` callback wraps the throwable in `RecordingFailed`, whose own
`report()` method logs through a bare logger (bypassing the event dispatcher) so
it cannot re-enter `record()`. Recording never breaks the application's event
flow. Through `dispatchAfterFailed()`, a failure is re-queued once. `LogEvent`
does nothing for an event that is not `Recordable`.

### The `EVENT_LOG_ENABLED` gate

`Providers\Provider::registerEventDispatcherDecorator()` installs the decorator
only when `config('event_log.enabled')` is true.

```php
if (! config('event_log.enabled')) {
    return;
}

$this->app->extend('events', fn ($original) => new Dispatcher($original));
```

When the package is disabled, the decorator is never bound. There is no recording
and no overhead.

### The `LogEvent` action

`Actions\LogEvent` is an `AsAction` and a `ShouldBeUnique` (its `uniqueId` is a
UUIDv7). It sets `$tries = 3` and `$backoff = [10, 60, 300]`. The decorator calls
it with `dispatchAfterFailed()`, so a synchronous failure re-queues once. The
action does this work:

- It keeps an un-serialized clone of the original event.
- It captures `occurredAt` and a cloned `Context` at dispatch time, not when the
  row is written.
- For a `RecordableAfterCommit`, it writes the `Log` after the transaction
  commits. For a `Recordable`, it writes immediately with rollback protection
  (`DB::afterRollBack`).
- It persists with `Log::createOrFirst(['idempotency_key' => $uniqueId], [...])`,
  which is idempotent on the unique key.

## Event integrity

The binary `event` column holds a signed copy of the event object. The
`Concerns\HasEvent` trait on `Log` owns it. The signature lets a reader find out
if someone changed the row.

**On write** (`setEventAttribute`):

1. It clones the event and calls `loggable->unsetRelations()->withoutAppends()`.
2. It serializes inside `Event::withoutSerializesModels(...)`, so the loggable is
   serialized by value, not as a model reference.
3. It signs the result. It prepends a SHA-256 HMAC keyed by `config('app.key')`:
   `hash_hmac('sha256', $serialized, $key) . $serialized`. A missing key throws
   `MissingAppKeyException`. The signature gates `unserialize()` on read, so it
   must never fall back to an empty key.
4. It base64-encodes the signed string.

The write step also force-fills three derived columns: `type` (from
`event->alias`), `loggable`, and `data` (from `event->loggable->toLoggable()`).

**On read** (`getEventAttribute`) returns one of three things —
`Recordable | Corrupted | Tampered`:

1. It base64-decodes. On failure it returns a `Logs\Integrity\Corrupted`.
2. It splits the 64-character hex signature, recomputes the HMAC, and compares
   with `hash_equals`. On a mismatch it returns a `Logs\Integrity\Tampered`.
3. Otherwise it unserializes back to the `Recordable`.

The read step never throws. A consumer detects a bad row through the `Corrupted`
or `Tampered` sentinel. Each sentinel is a `final readonly` object that holds the
offending `string $raw`.

### The SerializesModels opt-out

`Concerns\SerializesModels` wraps Laravel's `SerializesModels`. It overrides
`getSerializedPropertyValue()`. When the opt-out is active, it returns the raw
value instead of a model-identifier stub. This is how the loggable is embedded by
value in the signed blob.

The `Support\Events\Dispatcher\Mixins\DisablesSerializesModels` mixin adds
`withoutSerializesModels($events, $callback)` to Laravel's dispatcher (in
`Provider::bootMixins()`). The macro flips a static flag, runs the callback, and
always resets the flag in a `finally`. A PHPStan reflection extension models this
macro for static analysis (see [tooling.md](tooling.md)).

## Morph mapping

The package registers no morph aliases and requires no morph map. It stores morph
values through `getMorphClass()`:

- `Log::setLoggableAttribute()` sets `loggable_id = $model->getKey()` and
  `loggable_type = $model->getMorphClass()`.
- `Delivery::setRecipientAttribute()` does the same for `recipient_id` and
  `recipient_type`.

Whatever `getMorphClass()` returns is persisted as-is. This is the FQCN by
default, or a consumer's alias if they registered one. On read, `morphTo`
resolves either form. Morph mapping is the application's concern. A consumer who
wants aliased identifiers (including for this package's own `Log`) registers them
in their own service provider with `Relation::morphMap()` or
`Relation::enforceMorphMap()`. The package neither defines aliases nor forces
registration.

## Payload variance

`Loggable::toLoggable()` returns a `Logs\Data\Data`. The package builds it from
one or more `Logs\Data\Variant` objects:

- `Variant::make($payload, $version)` accepts an `Arrayable`, `JsonSerializable`,
  or `Jsonable` payload. It auto-discovers a `Version` from any public property of
  the payload. It falls back to the passed `$version`. It throws
  `Version\Exceptions\NotProvided` if neither yields a version.
- `Version` is `interface Version extends BackedEnum`. A consumer defines its own
  backed enum.
- `Data` is a `Castable` — the cast for the `data` column. On write it serializes
  to a JSON object keyed by each variant's `version->value`. On read it decodes
  back to a plain array. There is no flat single-variant form. A lone variant
  still produces `{ "v1": {...} }`.

So `Log.data` is a map of version to payload slice.

## The delivery identity: envelope as canonical key

A delivery is identified by its envelope — the recipient plus the version — not by
its raw columns. This convention has two halves.

**The write side.** `Delivery.fillable` is `['envelope', 'tries']`. The
`recipient_type`, `recipient_id`, and `version` columns are not fillable. You set
them by handing over an `Envelope`, which `setEnvelopeAttribute` force-fills into
place. So there is one way to author a delivery's identity.

**The read side.** `Deliveries\Builder` is a custom Eloquent builder (wired with
`#[UseEloquentBuilder]`). It overrides `where()`. Pass an `Envelope` and it
expands into the three identity columns through `Delivery::identify()`.

```php
Delivery::where('envelope', $envelope)->first();
Delivery::where(['envelope' => $envelope])->first();
$relay->deliveries()->firstOrCreate(['envelope' => $envelope], ['tries' => ...]);
```

The whole `firstOr*` family and the relation both route their match through
`where()`, so this single override covers every surface. The envelope expands as
one atomic group, wrapped in the caller's boolean. So `whereNot`, `orWhere`, `!=`,
and nested closures all behave as they would in stock Laravel —
`whereNot('envelope', $e)` is `NOT (type = … and id = … and version = …)`, not a
per-column negation. `Delivery::identify(Envelope)` is the shared translator for
both sides.

### Slicing at delivery-creation time

An `Envelopes\Envelope` (`make(Model $recipient, ?Version $version)`) expresses
which version a recipient wants. Relay `Process` creates a `Delivery` per
envelope. `Delivery::setEnvelopeAttribute` stores the recipient and the `version`
(as `envelope->version?->value`). The `Delivery::payload()` accessor resolves the
slice lazily.

```php
if ($this->version === null) {
    return $this->relay->log->data;          // whole map
}

$versioned = data_get($this->relay->log->data, $this->version);

return is_array($versioned) ? $versioned : null; // one slice
```

### Deliverability

`Delivery::isDeliverable()`:

```php
$this->recipient !== null
    && ($this->version === null || data_has($this->relay->log->data, $this->version));
```

A delivery is undeliverable for two reasons: the recipient was deleted (the
eager-loaded `recipient` relation is null), or the requested version was never
emitted. Both `payload` and `is_deliverable` are appended, and
`$with = ['relay.log', 'recipient']` makes sure the data is loaded. The
`Undeliverable` state is terminal and has no retry. It gives a durable, observable
record. An engineer can compare the delivery's `version` against the log's `data`
keys, or inspect the recipient for deletion. See [lifecycle.md](lifecycle.md) for
how the pipeline routes to it.

## Per-layer queue resolution

Each processing job runs on a queue owned by its layer, not chosen by the caller.
Every model exposes a `$queue` property. Every trigger has a `$queue` property
hook that reads its target model's `$queue`. So the value is baked onto the job
before dispatch. (There is no `viaQueue()` for a bus-dispatched command — the bus
reads the plain `$queue` property.) The hook is a get-only hook that recomputes
from the model, so it stays correct even after the job is serialized and restored.
The hook is on every trigger, not only `Process`. So if any trigger is ever
dispatched, it still lands on the layer queue.

The `#[Queues]` slots do not hold queue names. They hold config keys that the
transport owns. A transport ships its own config file (with an env-backed default)
and points the attribute at a key in it. The consumer customizes the queue through
that env. The transport author never hardcodes infrastructure. event-log resolves
the key through `config()`.

The layer's own fallback lives in `config('event_log.queues')`, keyed by model
class (`Log::class`, `Relay::class`, `Delivery::class`). So each model's `$queue`
reads `config('event_log.queues.'.static::class)` instead of a hardcoded string.
The config is not published. The provider merges it in, and the `EVENT_LOG_QUEUE_*`
env vars drive it. So the class-name keys stay internal to the package.

| Model | `$queue` resolves to |
|---|---|
| `Log` | `config('event_log.queues.'.Log::class)` |
| `Relay` | `config(`#[Queues(collecting:)] key`)` ?? `config('event_log.queues.'.Relay::class)` |
| `Delivery` | `config(`#[Queues(sending:)] key`)` ?? `config('event_log.queues.'.Delivery::class)` |
| `DeliveryAttempt` | `$this->delivery->queue` (defers to its Delivery) |

`Queues::on($transport)` (a static on the `#[Queues]` attribute) is the one place
that reflects the attribute off the transport. A missing attribute yields an empty
`Queues`, so both slots read null. A null key — or a key that resolves to null (an
unset env, or a typo) — falls through to the layer's own class-keyed entry, then
the framework default. So a bad key degrades to the layer queue instead of an
error. DeliveryAttempts process synchronously (`->now()`), so their own queue does
not matter in practice. But the model still exposes `$queue` (deferring to its
Delivery), so a dispatched attempt trigger would route sensibly.

`LogEvent` and the watchdog `Bite` objects use `AsAction` directly, not a base
class. PHP forbids a class from redeclaring a trait property as a hooked one, so
they cannot use the `$queue` hook. They assign
`$this->queue = config('event_log.queues.'.Model::class)` in their constructor
instead. This is the same resolved value (the layer config queue), set at
construction rather than on read.

## Concurrency: one worker per record

Each of the three queued process steps (Log, Relay, Delivery) carries
`WithoutOverlapping` middleware. The middleware keys the lock to the record —
`StatusEnum::class` plus the model key. So two workers cannot run the same
record's process step at the same time. A concurrent loser gives up
(`dontRelease()`) rather than wait, and the watchdog recovers a winner that then
crashes. The lock expires after `config('event_log.locking.ttl')` seconds (300 by
default), which must be longer than the slowest process step.

Each trigger declares this through the standard Laravel `middleware()` method, so
it reads like any other queued job:

```php
public function middleware(): array
{
    return [(new WithoutOverlapping($this->to::class.':'.$this->delivery->getKey()))
        ->dontRelease()
        ->expireAfter(config('event_log.locking.ttl'))];
}
```

The three process steps are the only place a duplicate does real work — they
create children or send a message. The other transitions (`Fail`, `Succeed`,
`Disqualify`, `Compromise`) have empty handlers, so a duplicate is harmless. The
execution-time transition guard (see
[state-machines.md](state-machines.md#trigger-base-mechanics)) makes a
redelivered process step on an already-advanced record a no-op.

## The watchdog

A worker can die after it does its work but before the queue acknowledges the job.
The record then stays stuck in an in-flight state (`Pending` or `Locked`). The
watchdog is the backstop for this.

Each tier has a `Watchdog` (`{Tier}/Watchdog/Watchdog.php`). Its `bite()` returns
a `Bite` action rather than running the sweep. So the caller decides `->now()`
versus `->dispatch()`. `Bite::handle()` sweeps its tier's stuck records and fails
each one.

```php
Relay::query()
    ->stuck()
    ->eachById(fn (Relay $relay) => rescue(fn () => $relay->status->fail()->now()));
```

`stuck()` lives on each tier's custom `Builder`. It matches a status of `Pending`
or `Locked` and an `updated_at` older than
`now()->subMinutes(config('event_log.watchdog.grace'))`. `rescue()` isolates a bad
row from the rest of the sweep. `eachById` chunks the scan. A failed stuck record
goes through the normal `Fail` transition, so it lands in the same terminal state
as any other failure — a durable, observable record instead of an orphan.

Each tier registers its own command
(`{Tier}/Watchdog/Console/Commands/Watchdog.php`, `event-log:{tier}:watchdog`).
The command resolves the tier's `Bite` and, by default, `->dispatch()`es it.
`--sync` runs it inline. Separate commands let each tier run on its own schedule.
See [lifecycle.md](lifecycle.md#the-watchdog) for the operational detail.

Each `Bite` sets its `$queue` in the constructor to
`config('event_log.queues.'.Model::class)` — the layer config queue, keyed by
model class. (This is a constructor assignment, not a hook, because `Bite` uses
`AsAction` directly.) A sweep spans the whole table, not one transport, so there
is no `#[Queues]` slot to consult. It uses the layer config queue only. So a
`->dispatch()`ed sweep runs on the same layer queue its processing jobs use.

## Performance and indexing

`event_logs` can be a high-write, fast-growing table. The indexing strategy makes
the hot read paths fast and keeps the write path cheap.

The dominant read is a list sorted by `occurred_at` descending. So the indexes
lead with `(occurred_at, id)`. Sort by `occurred_at` then `id` (the `id` breaks
millisecond ties) and the query is a straight index scan. Cursor pagination fits
this best, but how you paginate is a query-time choice. The schema just makes it
fast.

### Indexes

| Table | Index | For |
|---|---|---|
| `event_logs` | `(occurred_at, id)` | the index page |
| `event_logs` | `(type, occurred_at, id)` | the index page filtered to one event type |
| `event_logs` | `(loggable_type, loggable_id, occurred_at, id)` | a model's own log, e.g. `$loggable->eventLogs()` |
| `event_log_deliveries` | `(recipient_type, recipient_id, id)` | a recipient's deliveries, e.g. `$recipient->deliveries()` |

Each index leads with its filter columns, then its sort columns. So a filtered
list is one seek, not a filter-then-sort. The child tables sort by `id` (a UUIDv7,
already time-ordered), so they need no timestamp column.

Relationship walks (for example `relay->log`, the eager loads, and `find()`) use
the primary and foreign keys. These are already fast on their own.

The morph columns are declared by hand, not by `uuidMorphs`. So the only index is
the wide composite. The default narrow `(type, id)` morph index would be a subset
of it, which would add a write cost for no read benefit.

The `$loggable->eventLogs()` and `$recipient->deliveries()` reverse relationships
are yours to declare. These indexes back them.

### What we do not index, and why

Every index taxes each insert. So the package adds only the ones with a real query
behind them:

- **`status`** — the watchdog filters on it (`stuck()` scans `Pending`/`Locked`
  by `updated_at`). But that is a periodic background sweep, not a hot path, so it
  does not justify the per-insert cost yet. Add a `(status, updated_at)` composite
  if the sweep or a dashboard ever needs it.
- **`relays.transport`** — discovery inspects code, not the database.

UUIDv7 keys append to the end of each index instead of scattering. So writes stay
cheap as the table grows.

### Reporting

Run broad or all-time reporting (including exact `COUNT(*)` totals) on a read
replica. Those scans fight the write path and evict hot index pages from the
cache. This is also the reason to avoid an exact total on an index page — counting
the whole table is expensive no matter the indexing.

### Autovacuum

A log row is written once and never updated after it finishes. So its pages freeze
and autovacuum skips them. Tune autovacuum to freeze aggressively and to keep up
with inserts. Do this at the database layer, not in migrations, so the migrations
stay portable to SQLite in tests.

### If we ever partition

If vacuum or index rebuilds on `event_logs` start to hurt, partition it by
`occurred_at`. Postgres cannot do that in place — it is a new-table-and-swap, so
plan it deliberately. There are two catches. Every unique or primary key must
include `occurred_at` (so the idempotency key becomes partition-scoped). And id
lookups like `relay->log` cannot skip partitions unless you copy `occurred_at`
onto the child tables. The payoff is cheaper vacuum and the ability to drop old
data by partition. It does not improve query speed the indexes already give.
