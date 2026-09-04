# Lifecycle

This document describes how the `Log → Relay → Delivery → DeliveryAttempt` cascade
starts and runs. For the state machines, see
[state-machines.md](state-machines.md). For the models and tables, see
[architecture.md](architecture.md). For a top-down overview, start at
[README.md](README.md).

## The repeating shape

Every tier follows the same four-step shape:

```
create → lock → process → children
```

1. **Create.** Something inserts a row. The parent tier does this, or (for the
   Log) the recording path does.
2. **Lock.** The row's `created` model event fires a listener. The listener locks
   the row, which moves it to `Locked`.
3. **Process.** The lock step starts the process step. The process step does the
   tier's real work.
4. **Children.** The process step creates the rows for the tier below. Each new
   row starts the same shape one tier down.

The last tier (DeliveryAttempt) has no children. Its process step sends the
message instead.

## Initiation

Each tier has an `InitiateLifecycle` listener bound to that tier's Eloquent
`created` model event. When a row is created, the listener locks it and the
lifecycle begins.

| Listener | Listens on | Action | Wrapped in `rescue()`? |
|---|---|---|---|
| `Logs\Listeners\InitiateLifecycle` | `Logs\Events\Created` | `log->status->lock()->now()` | yes |
| `Relays\Listeners\InitiateLifecycle` | `Relays\Events\Created` | `relay->status->lock()->now()` | yes |
| `Deliveries\Listeners\InitiateLifecycle` | `Deliveries\Events\Created` | `delivery->status->lock()->now()` | yes |
| `DeliveryAttempts\Listeners\InitiateLifecycle` | `DeliveryAttempts\Events\Created` | `deliveryAttempt->status->lock()->now()` | **no** |

`Providers\Provider::bootListeners()` wires these. The `Created` events are the
standard Eloquent model events declared in each model's `$dispatchesEvents`.

The DeliveryAttempts listener is the one that is not wrapped in `rescue()`. This is
the only asymmetry in the set.

## The creation cascade

```
LogEvent creates Log
  └─ Log created → Log Lock → Log Process (queued)
        creates one Relay per transport (event->transports)
        └─ Relay created → Relay Lock → Relay Process (queued)
              dispatches the collecting event, gathers Envelopes,
              creates one Delivery per Envelope
              └─ Delivery created → Delivery Lock → Delivery Process (queued)
                    creates a DeliveryAttempt
                    └─ DeliveryAttempt created → Attempt Lock → Attempt Process (now)
                          dispatches the sending event, records the result
```

Each `Created` event re-enters the same lock-then-process pattern one tier down.

## Sync and async boundaries

The `Lock` trigger of a tier hands off to that tier's `Process` trigger. The
boundary differs by tier.

| Tier | Lock → Process | Boundary |
|---|---|---|
| Log | `process()->dispatch()->afterCommit()` | queued (after the Lock transition commits) |
| Relay | `process()->dispatch()->afterCommit()` | queued (after the Lock transition commits) |
| Delivery | `process()->dispatch()->afterCommit()` | queued (after the Lock transition commits) |
| DeliveryAttempt | `process()->now()` | **synchronous** |

So Log, Relay, and Delivery each cross a queue boundary between the lock step and
the process step. Each process step is a separate job. A DeliveryAttempt processes
inline, inside the Delivery's process job.

The `Lock` triggers just dispatch. They do not pick the queue. Every trigger has a
`$queue` property hook that reads its target model's `$queue`, so the queue belongs
to the layer, not the caller. Because the hook is on every trigger (not only
`Process`), the routing stays correct no matter which trigger is dispatched.

Each layer resolves its queue like this. The layer fallback is keyed by model
class.

| Layer | Resolves to |
|---|---|
| Log | `config('event_log.queues.'.Log::class)` |
| Relay | `config(` transport `#[Queues(collecting:)]` key `)` → `config('event_log.queues.'.Relay::class)` → default |
| Delivery | `config(` transport `#[Queues(sending:)]` key `)` → `config('event_log.queues.'.Delivery::class)` → default |

The `#[Queues]` slots hold config keys that the transport owns, not queue names. A
transport ships its own config file with an env-backed default (for example,
`'sending' => env('WEBHOOKS_SENDING_QUEUE')`) and points the attribute at that key
(`#[Queues(sending: 'webhooks.queues.sending')]`). The consumer sets the env. The
transport author never hardcodes a queue name. If the key is unset (or a typo), the
layer's own class-keyed entry is used, then the framework default. See
[architecture.md](architecture.md#per-layer-queue-resolution) for the full detail.

## The Delivery process step

The Delivery process step is where the cascade reaches the recipient. Its
`handle()` does this:

```php
public function handle(): void
{
    $this->delivery->touch();

    if (! $this->delivery->is_deliverable) {
        $this->delivery->status->disqualify()->dispatchAfterFailed()->now();

        return;
    }

    try {
        $this->delivery->attempts()->create();
    } catch (Undeliverable) {
        $this->delivery->status->disqualify()->dispatchAfterFailed()->now();

        return;
    }

    $this->delivery->status->succeed()->dispatchAfterFailed()->now();
}
```

The first line calls `touch()`. This updates the delivery's `updated_at` at the
start of every process run, so the watchdog does not treat a delivery that is
still working as stuck (see [The watchdog](#the-watchdog)).

There are two routes to `Undeliverable`:

1. **Pre-flight.** `is_deliverable` is false. The recipient was deleted, or the
   requested version was never emitted. No `DeliveryAttempt` is created.
2. **During send.** An `Undeliverable` exception comes up from the attempt's send.
   The process step catches it and disqualifies the delivery.

`Undeliverable` is terminal. It has no retry. It leaves a durable, observable
record.

## Redelivery safety

Queues are at-least-once. A worker can die after it does its work but before the
job is acknowledged, and the job runs again. Three mechanisms keep the pipeline
correct under this.

**Idempotent creation.** The child-creating steps absorb a re-run. Log `Process`
uses `firstOrCreate` on `(event_log_id, transport)`. Relay `Process` uses
`firstOrCreate` on the envelope identity. So a re-run finds the rows the first run
already created instead of duplicating them.

**One worker per record.** The three process steps (Log, Relay, Delivery) carry
`WithoutOverlapping` middleware keyed to the record. So only one worker can execute
a given record's process step at a time. A concurrent loser gives up
(`dontRelease()`). The watchdog backstops a winner that then crashes. See
[architecture.md](architecture.md#concurrency-one-worker-per-record) for the
detail.

**The last hop.** The actual send in DeliveryAttempt `Process` cannot be deduped
by a database write, because it is a network call. For that, the sending event
exposes `idempotencyKey` (the delivery id, stable across attempts and retries). A
transport can stamp it on the outbound message, and the recipient can ignore a
duplicate. The package produces the key. The transport stamps and dedupes.

Together these cover sequential re-runs (crash, then redelivery) and two workers
that race the same row at the same instant.

## Failure handling

If a `Process` trigger throws, its `failed()` hook drives the record toward a
terminal state — `Failed` (or `Undeliverable`, or `Compromised` for a Log). The
terminal transition triggers (`Fail`, `Disqualify`, `Succeed`, `Compromise`) run
synchronously with `->now()` from inside these hooks. Those calls chain
`dispatchAfterFailed()`. So if the terminal transition itself fails synchronously,
it is re-queued once instead of leaving the record stuck in `Locked`. A permanently
failing transition dead-letters to `failed_jobs` instead of looping. See
[state-machines.md](state-machines.md) for the per-trigger detail.

If a record slips past all of this and sits in an in-flight state (`Pending` or
`Locked`) longer than it should — a worker dies between the lock and the process,
for example — the watchdog is the backstop.

Retries at the Delivery tier are owned by the `Retry` trigger. It increments
`tries` and re-locks. DeliveryAttempts have no `Retry`. Retrying is the parent
Delivery's responsibility.

## The watchdog

At-least-once queues and synchronous re-dispatch cover the failures the package can
see. But a record can still get stranded in an in-flight state (`Pending` or
`Locked`) if a worker dies at exactly the wrong moment. The watchdog sweeps those
up.

Each tier has its own `Watchdog` (under `{Tier}/Watchdog/`). Its `bite()` returns a
`Bite` action. It does not run anything itself, so the caller decides whether to
run it now or queue it.

```php
Relay::watchdog()->bite()->now();
```

If you `->dispatch()` a `Bite` instead, it lands on that tier's layer queue
(`config('event_log.queues.'.Model::class)`) — the same queue the tier's processing
jobs use. So the sweep does not jump ahead of the work it cleans up.

`Bite::handle()` finds the stuck records for its tier and fails each one.

```php
Relay::query()
    ->stuck()
    ->eachById(fn (Relay $relay) => rescue(fn () => $relay->status->fail()->now()));
```

`stuck()` is a query-builder scope shared by all four tiers. It matches a status of
`Pending` or `Locked` and an `updated_at` older than the grace cutoff
(`now()->subMinutes(config('event_log.watchdog.grace'))`, 15 minutes by default).
Each fail is wrapped in `rescue()`, so one bad row does not stop the sweep.
`eachById` chunks the scan, so a large backlog does not load into memory at once.

A failed stuck record routes through the normal `Fail` transition. So it lands in
the same terminal `Failed` state (and fires the same events) as any other failure.
It becomes a durable, observable record instead of a silent orphan.

Set `event_log.watchdog.grace` comfortably longer than the slowest legitimate
single step, including the longest backoff wait between delivery attempts. Then the
watchdog never fails a record that is only slow.

### Running it

The package registers one command per tier. So each tier runs on its own schedule.

```
php artisan event-log:logs:watchdog
php artisan event-log:relays:watchdog
php artisan event-log:deliveries:watchdog
php artisan event-log:delivery-attempts:watchdog
```

Each command defaults to `->dispatch()`. The sweep runs as a queued job on the
tier's layer queue. Pass `--sync` to run it inline instead (useful for a one-off
from the CLI). Schedule the commands to fit each tier's grace period. The sweep is
idempotent, so running it more often than needed is harmless.
