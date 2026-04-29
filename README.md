# Event Log

This package provides an opinionated way to record
class-backed Laravel events to the database.

## Installation

```bash
composer require aryeo/event-log
```

## Overview

Event Log is comprised of three parts:

- **Recordable**: An interface marking events that should be logged.
- **Loggable**: A model contract that defines the data snapshot to persist.
- **Dispatcher**: A decorator around Laravel's Event Dispatcher that intercepts
  and records events _before_ listeners are invoked.

Because recording happens inside a Dispatcher decorator — not a Listener — the
result of any Listener registered to a given event can never prevent the event
record from being stored. This guarantees at-least-once recording while
preserving the full feature set and expectations of the framework's
Event / Listener pipeline.

## Usage

### Define a Recordable Event

A `Recordable` event requires three things:

1. Implement `Recordable` (or `RecordableAfterCommit`).
2. Use the `HasLoggable` trait.
3. Mark the model property with `#[IdentifiesLoggable]` and the class with
   `#[Alias]`.

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

> **Note:** The `#[Alias]` attribute defines the event's type string stored in
> the `event_logs.type` column. The `#[IdentifiesLoggable]` attribute identifies
> which property holds the related `Loggable` model. Both are required — a
> missing attribute will throw an exception.

### Implement Loggable on a Model

The `Loggable` interface requires a single method — `toLoggable()` — that
returns the data snapshot persisted to the `event_logs.data` column.

```php
namespace Articles;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Contracts\Loggable;

class Article extends Model implements Loggable
{
    /**
     * @return array<string, mixed>
     */
    public function toLoggable(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->title,
        ];
    }
}
```

### Dispatch the Event

Dispatch the event using standard Laravel conventions. The decorator intercepts
it automatically — no extra wiring is needed.

```php
use Articles\Article;
use Articles\Events\Updated;

$article = Article::find($id);

Updated::dispatch($article);
```

### RecordableAfterCommit

`Recordable` events are logged immediately with rollback protection — if the
surrounding transaction is rolled back, the log record is still created after the
rollback completes. This is ideal for "pre" operation events (e.g. `Updating`)
where the attempt itself should always be recorded regardless of whether the
operation succeeds.

`RecordableAfterCommit` events are logged only after the surrounding transaction
commits. This is ideal for "post" operation events (e.g. `Updated`) where the
log record should only exist if the operation was successful.

```php
namespace Articles\Events;

use Articles\Article;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;

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

### The Log Record

Each recorded event produces a row in the `event_logs` table:

| Column | Description |
|---|---|
| `type` | The alias string from `#[Alias]` (e.g. `article.updated`). |
| `data` | JSON snapshot returned by `toLoggable()`. |
| `event` | The HMAC-signed, serialized PHP event object. |
| `context` | JSON snapshot of Laravel's `Context` at dispatch time. |
| `loggable_type` / `loggable_id` | Polymorphic relationship to the `Loggable` model. |
| `occurred_at` | Timestamp captured at the moment the event was dispatched. |
| `idempotency_key` | UUID ensuring at-least-once delivery without duplicates. |

### Context

Laravel's `Context` is captured at the moment the event is dispatched and stored
in the `context` column. This preserves the full application context (e.g. the
authenticated user, request ID) even when the log record is written later — after
a transaction commits or on the queue.

Use the `context.whitelist` configuration option to limit which top-level Context
keys are persisted.

### Integrity Signing

Every serialized event is signed with an HMAC-SHA256 hash using the application's
`APP_KEY`. The 64-character hex digest is prepended to the serialized payload
before it is stored in the `event` column. When the event is read back, the HMAC
is verified — if the payload has been tampered with, an `IntegrityViolation`
exception is thrown.

This protects event records from tampering once written. To benefit from integrity
verification, your application must have an `APP_KEY` set.

## Configuration

| Variable | Default | Description |
|---|---|---|
| `EVENT_LOG_ENABLED` | `true` | Toggle the Event Dispatcher decorator on or off. |
| `EVENT_LOG_CONTEXT_WHITELIST` | `""` | Comma-separated list of top-level Context keys to persist. Only listed keys are stored; an empty value persists no keys. |
