# Event Log

An opinionated package for recording class-backed Laravel events to the database,
with an optional transport-agnostic delivery pipeline for relaying those events
to external recipients (webhooks, MQTT, push notifications, and the like).

## Installation

```bash
composer require aryeo/event-log
```

The service provider auto-registers. Recording is enabled by default; set
`EVENT_LOG_ENABLED=false` to turn it off entirely.

Integrity signing requires your application's `APP_KEY`. Recording an event without one throws `MissingAppKeyException`, so make sure a key is set.

## Overview

Event Log has two layers:

1. **Recording** — a decorator around Laravel's event dispatcher intercepts
   events that implement `Recordable` and writes them to the `event_logs` table
   _before_ any listener runs. Because recording happens in the dispatcher, no
   listener can prevent an event from being recorded — recording is at-least-once
   and idempotent.
2. **Relays** (optional) — events can additionally opt into a delivery pipeline.
   Each recorded event fans out to one or more transports, each transport
   produces deliveries (one per recipient), and each delivery is attempted and
   tracked through its own state machine.

Use layer 1 alone if you only need an audit trail. Add layer 2 when you need to
deliver those events somewhere.

---

## Recording Events

### 1. Define a Recordable event

A `Recordable` event requires four things:

1. Implement `Recordable` (or `RecordableAfterCommit`).
2. Use the `HasLoggable` trait.
3. Mark one `Model & Loggable` property with `#[IdentifiesLoggable]`.
4. Mark the class with `#[Alias]`.

```php
namespace Articles\Events;

use Articles\Article;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;

#[Alias('article.updated')]
final class Updated implements Recordable
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
```

> `#[Alias]` defines the string stored in `event_logs.type` (e.g.
> `article.updated`). `#[IdentifiesLoggable]` marks which property holds the
> related model. Both are required; a missing attribute throws when the event is
> recorded. (These requirements are also enforced statically — see
> [docs/tooling.md](docs/tooling.md).)

### 2. Implement `Loggable` on the model

The `Loggable` contract requires a single method, `toLoggable(): Data`, that
returns the payload snapshot persisted to `event_logs.data`.

```php
namespace Articles;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Data\Variant;

class Article extends Model implements Loggable
{
    public function toLoggable(): Data
    {
        return Data::of(
            Variant::make($this, version: PayloadVersion::V1),
        );
    }
}
```

`Data::of(Variant ...$variants)` wraps one or more payload variants. A `Variant`
takes an object payload (anything `Arrayable`, `JsonSerializable`, or `Jsonable`
— an Eloquent model, a `JsonResource`, a `collect([...])`, etc.) and a `Version`.
See [Payload Versioning](#payload-versioning) below.

### 3. Dispatch normally

```php
use Articles\Article;
use Articles\Events\Updated;

Updated::dispatch(Article::find($id));
```

The dispatcher decorator records the event automatically — no extra wiring.

### Recordable vs. RecordableAfterCommit

- **`Recordable`** — recorded immediately, with rollback protection. If the
  surrounding transaction rolls back, the log row is still written afterward.
  Ideal for "pre" events (e.g. `Updating`) where the attempt should always be
  recorded.
- **`RecordableAfterCommit`** — recorded only after the surrounding transaction
  commits. Ideal for "post" events (e.g. `Updated`) where the record should exist
  only if the operation succeeded.

```php
use Support\Events\Log\Contracts\RecordableAfterCommit;

#[Alias('article.updated')]
final class Updated implements RecordableAfterCommit
{
    use HasLoggable;

    #[IdentifiesLoggable]
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
```

### The log record

Each recorded event produces a row in `event_logs`:

| Column | Description |
|---|---|
| `type` | The `#[Alias]` string (e.g. `article.updated`). |
| `data` | JSON payload from `toLoggable()`, keyed by version value. |
| `event` | The HMAC-signed, serialized event object. |
| `context` | Snapshot of whitelisted `Context` keys at dispatch time. |
| `loggable_type` / `loggable_id` | Polymorphic reference to the `Loggable` model. |
| `occurred_at` | When the event was dispatched. |
| `idempotency_key` | UUID guaranteeing at-least-once recording without duplicates. |
| `status` | Internal lifecycle state. |

### Reading events back

The `event` column round-trips back into your original event object. Because the
stored blob is HMAC-signed, reading it returns one of three things — handle the
sentinels rather than assuming you always get your event:

```php
use Support\Events\Log\Logs\Integrity\Corrupted;
use Support\Events\Log\Logs\Integrity\Tampered;

$event = $log->event; // Recordable | Corrupted | Tampered

match (true) {
    $event instanceof Corrupted => /* blob wasn't valid base64 */,
    $event instanceof Tampered  => /* signature didn't verify */,
    default                     => /* your Recordable event */,
};
```

---

## Payload Versioning

`toLoggable()` returns a `Data` object built from one or more `Variant`s. Each
variant is stored under its version's backed value, so `event_logs.data` is a map
of version → payload.

Define a version enum implementing `Version`:

```php
use Support\Events\Log\Logs\Data\Version\Contracts\Version;

enum PayloadVersion: string implements Version
{
    case V1 = 'v1';
    case V2 = 'v2';
}
```

### Single variant

```php
public function toLoggable(): Data
{
    return Data::of(
        Variant::make($this, version: PayloadVersion::V1),
    );
}
```

Stored: `{"v1": {"id": "...", "title": "..."}}`

### Multiple variants

Return several variants when different recipients need different payload shapes.
Each is stored under its own version key:

```php
public function toLoggable(): Data
{
    return Data::of(
        Variant::make(collect(['order_id' => $this->getKey()]), version: PayloadVersion::V1),
        Variant::make($this->toResource(), version: PayloadVersion::V2),
    );
}
```

Stored: `{"v1": {"order_id": 1}, "v2": {...}}`

> A `Variant` can also carry its version _inside_ the payload: if any public
> property of the payload object is a `Version`, it is discovered automatically
> and takes precedence over the `version:` argument. If no version can be
> resolved either way, `Variant::make` throws.

---

## Relays

Relays deliver recorded events to external recipients through pluggable
transports. When an event opts into a transport, the pipeline fans it out:

```
Log ─▶ Relay (per transport) ─▶ Delivery (per recipient) ─▶ DeliveryAttempt
```

Each stage is automatic and tracked by its own state machine. You implement two
things per transport: how recipients are gathered, and how a delivery is sent.

### 1. Define a transport

A transport is an **interface** that extends `Transport` and declares, via
`#[Dispatches]`, the two events that drive its pipeline:

- a **collecting** event (implements `NeedsEnvelopes`) — dispatched once per
  relay to gather recipients.
- a **sending** event (implements `NeedsSent`) — dispatched once per delivery to
  perform the actual send.

```php
namespace App\Webhooks;

use App\Webhooks\Events\NeedsWebhookEnvelopes;
use App\Webhooks\Events\NeedsWebhookSent;
use Support\Events\Log\Deliveries\Tries\Tries;
use Support\Events\Log\Transports\Contracts\Transport;
use Support\Events\Log\Transports\Dispatches\Dispatches;

#[Dispatches(collecting: NeedsWebhookEnvelopes::class, sending: NeedsWebhookSent::class)]
#[Tries(3)]
interface Webhookable extends Transport {}
```

Optional attributes:

- `#[Tries(int)]` — how many attempts each delivery gets (seeds `Delivery.tries`).
- `#[Queues(collecting: '...', sending: '...')]` — **config keys** (not queue
  names) for routing the relay and delivery processing jobs. Ship a config file
  with an env-backed default and point the attribute at it, e.g.
  `#[Queues(sending: 'webhooks.queues.sending')]` with
  `'sending' => env('WEBHOOKS_SENDING_QUEUE')`. That way the ultimate consumer
  customizes the queue through your env var; an unset key falls back to the
  event-log layer queue.

### 2. Define the collecting event

The collecting event receives the `Relay` and collects `Envelope`s. Use the
`CollectsEnvelopes` trait:

```php
namespace App\Webhooks\Events;

use Support\Events\Log\Relays\Relay;
use Support\Events\Log\Transports\Dispatches\Collecting\Contracts\NeedsEnvelopes;
use Support\Events\Log\Transports\Dispatches\Collecting\Provides\CollectsEnvelopes;

final class NeedsWebhookEnvelopes implements NeedsEnvelopes
{
    use CollectsEnvelopes;

    public readonly Relay $relay;

    public function __construct(Relay $relay)
    {
        $this->relay = $relay;
    }
}
```

### 3. Define the sending event

The sending event receives the `Delivery` and records a result. Use the
`RecordsResult` trait:

```php
namespace App\Webhooks\Events;

use Support\Events\Log\Deliveries\Delivery;
use Support\Events\Log\Transports\Dispatches\Sending\Contracts\NeedsSent;
use Support\Events\Log\Transports\Dispatches\Sending\Provides\RecordsResult;

final class NeedsWebhookSent implements NeedsSent
{
    use RecordsResult;

    public readonly Delivery $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
}
```

`RecordsResult` also exposes `$event->idempotencyKey` — a stable key (the delivery
id) that is the same across every attempt and retry of a delivery. If your
transport sends over a channel where the recipient can dedupe (an idempotency
header, a message id), stamp this on the outbound message so a redelivered job
doesn't double-send. Nothing sends twice on our side by design, but the actual
network send is the one hop a database can't make idempotent — this key is how
the recipient closes it.

### 4. Implement the listeners

The collecting listener resolves recipients and adds an `Envelope` for each. A
recipient is any Eloquent model:

```php
namespace App\Webhooks\Listeners;

use App\Models\WebhookSubscription;
use App\Webhooks\Events\NeedsWebhookEnvelopes;
use Support\Events\Log\Envelopes\Envelope;

final class GatherEnvelopes
{
    public function handle(NeedsWebhookEnvelopes $event): void
    {
        WebhookSubscription::query()
            ->where('event_type', $event->relay->log->type)
            ->each(fn (WebhookSubscription $subscription) => $event->add(
                Envelope::make(recipient: $subscription),
            ));
    }
}
```

The sending listener has three possible outcomes:

- **Success** — record a result with `$event->result(...)`. The value is stored
  on the delivery attempt's `response`.
- **Retryable failure** — throw `Failed` (or let any exception bubble up). The
  delivery moves to `Failed` and is retried up to the transport's `#[Tries]`
  budget.
- **Permanent failure** — throw `Undeliverable`. The delivery moves to the
  terminal `Undeliverable` state and is **not** retried.

In both throwing cases the exception message is stored on the attempt's
`response`.

```php
namespace App\Webhooks\Listeners;

use App\Webhooks\Events\NeedsWebhookSent;
use Illuminate\Support\Facades\Http;
use Support\Events\Log\DeliveryAttempts\Exceptions\Failed;
use Support\Events\Log\DeliveryAttempts\Exceptions\Undeliverable;

final class SendWebhook
{
    public function handle(NeedsWebhookSent $event): void
    {
        if ($event->delivery->recipient->unsubscribed) {
            // No point retrying — this recipient will never accept the delivery.
            throw new Undeliverable('recipient unsubscribed');
        }

        $response = Http::post(
            $event->delivery->recipient->url,
            $event->delivery->payload,
        );

        // A transient upstream failure — let it retry.
        throw_if($response->serverError(), new Failed("upstream {$response->status()}"));

        $event->result($response->status());
    }
}
```

### 5. Wire the listeners

Register both listeners in a service provider:

```php
namespace App\Webhooks;

use App\Webhooks\Events\NeedsWebhookEnvelopes;
use App\Webhooks\Events\NeedsWebhookSent;
use App\Webhooks\Listeners\GatherEnvelopes;
use App\Webhooks\Listeners\SendWebhook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class WebhookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(NeedsWebhookEnvelopes::class, GatherEnvelopes::class);
        Event::listen(NeedsWebhookSent::class, SendWebhook::class);
    }
}
```

### 6. Make an event relayable

Mark the event with the transport interface and use the `HasRelays` trait. An
event can join multiple transports by implementing multiple transport interfaces:

```php
namespace Articles\Events;

use App\Webhooks\Webhookable;
use Articles\Article;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Support\Events\Log\Provides\HasRelays;

#[Alias('article.updated')]
final class Updated implements Webhookable, RecordableAfterCommit
{
    use HasLoggable;
    use HasRelays;

    #[IdentifiesLoggable]
    public Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
```

`HasRelays` discovers which transports an event belongs to by reflecting over the
interfaces it implements.

### Envelopes

An `Envelope` describes a single delivery target. A recipient is required; a
version optionally selects which payload slice the recipient receives:

```php
use Support\Events\Log\Envelopes\Envelope;

// Deliver the full payload to a recipient
Envelope::make(recipient: $subscription);

// Deliver a specific version slice
Envelope::make(recipient: $subscription, version: PayloadVersion::V1);
```

When a version is given, the matching slice of the log's `data` is materialized
into the delivery's `payload`. When omitted, the full `data` snapshot is used.

An envelope is also how you identify a delivery, not just create one. A delivery's
identity is its recipient plus version, and you query by that identity with the
envelope itself — no need to know the underlying columns:

```php
Delivery::where('envelope', Envelope::make(recipient: $subscription))->first();
```

This works in every `where` shape (`where`, `whereNot`, `orWhere`, `!=`, nested
closures) exactly as a normal column would. The raw `recipient`/`version` columns
aren't mass-assignable — the envelope is the one way to write a delivery, too.

### What a delivery exposes

In a sending listener, the `Delivery` gives you everything needed to deliver:

| Property | Description |
|---|---|
| `$delivery->recipient` | The recipient model (eager-loaded). |
| `$delivery->payload` | The resolved payload slice (array), or `null` if undeliverable. |
| `$delivery->version` | The requested version string, or `null` for the full payload. |
| `$delivery->is_deliverable` | `false` if the recipient was deleted or the requested version isn't present. |
| `$delivery->relay->log` | The originating log (`->type`, `->data`, etc.). |

A delivery whose recipient has been deleted, or whose requested version was never
emitted, is automatically marked `Undeliverable` — the sending listener is never
invoked for it.

---

## Configuration

| Variable | Default | Description |
|---|---|---|
| `EVENT_LOG_ENABLED` | `true` | Toggles the dispatcher decorator. When `false`, nothing is recorded. |
| `EVENT_LOG_CONTEXT_WHITELIST` | `""` | Comma-separated list of top-level `Context` keys to persist. Only listed keys are stored. |
| `EVENT_LOG_QUEUE_LOG` | _(default queue)_ | Queue the Log processing job runs on. |
| `EVENT_LOG_QUEUE_RELAY` | _(default queue)_ | Queue the Relay processing job runs on. A transport's `#[Queues(collecting:)]` overrides this. |
| `EVENT_LOG_QUEUE_DELIVERY` | _(default queue)_ | Queue the Delivery processing job runs on. A transport's `#[Queues(sending:)]` overrides this. |
| `EVENT_LOG_WATCHDOG_GRACE` | `15` | Minutes a record may sit in `Pending`/`Locked` before the watchdog fails it. |

### Context

Laravel's `Context` is captured at dispatch time and stored on the log, so the
full application context (authenticated user, request ID, etc.) is preserved even
when the row is written later — after a commit or on the queue. Only the keys
listed in `EVENT_LOG_CONTEXT_WHITELIST` are persisted; everything else is dropped
before the row is written.

### Integrity signing

Every stored event is signed with an HMAC-SHA256 hash keyed by your `APP_KEY`.
On read, the signature is verified: a tampered payload yields a `Tampered`
object and an unreadable one yields a `Corrupted` object, rather than silently
returning bad data. The signature gates `unserialize()` on the stored blob, so a
key is mandatory — recording without an `APP_KEY` throws `MissingAppKeyException`
rather than signing with an empty key.

### Queues

The Log, Relay, and Delivery processing steps each run as a queued job, and each
layer's queue is configurable independently — later steps tend to be more
expensive than earlier ones, so you can route them separately. Each layer resolves
its queue as: the transport's `#[Queues]` slot (relay uses `collecting:`, delivery
uses `sending:`), then the matching `EVENT_LOG_QUEUE_*` config, then the framework
default. The Log layer has no transport, so it's config-only. DeliveryAttempts run
synchronously and aren't queued.

The transport's `#[Queues]` slots hold **config keys the transport owns**, not
queue names — so a transport author exposes their *own* env var for routing (see
[Define a transport](#1-define-a-transport)) and never hardcodes a queue. An
unset or mistyped key just falls back to the layer's `EVENT_LOG_QUEUE_*`.

### Watchdog

At-least-once queues mean a record can occasionally get stranded in an in-flight
state (`Pending` or `Locked`) — for example if a worker dies between locking a
record and processing it. Each tier has its own command that sweeps records
in-flight longer than `EVENT_LOG_WATCHDOG_GRACE` minutes and fails them through
the normal transition, so they become durable, observable `Failed` records instead
of silent orphans:

```
php artisan event-log:watchdog:logs
php artisan event-log:watchdog:relays
php artisan event-log:watchdog:deliveries
php artisan event-log:watchdog:delivery-attempts
```

Each queues the sweep by default (add `--sync` to run inline). Schedule them to
match each tier's grace period; the sweep is idempotent. See
[docs/lifecycle.md](docs/lifecycle.md#the-watchdog) for details.

---

## Architecture & internals

Design and maintenance documentation lives in [docs/](docs/):

- [docs/architecture.md](docs/architecture.md) — the four-tier pipeline, the
  dispatcher decorator, event serialization/integrity, morph mapping, and payload
  variance resolution.
- [docs/lifecycle.md](docs/lifecycle.md) — how the Log → Relay → Delivery →
  DeliveryAttempt cascade is initiated and driven, including the sync/async
  boundaries and undeliverable routing.
- [docs/state-machines.md](docs/state-machines.md) — every tier's state machine,
  transition tables, triggers, and the rendered lifecycle diagrams.
- [docs/tooling.md](docs/tooling.md) — the custom PHPStan rules that enforce the
  package's contracts at static-analysis time.
