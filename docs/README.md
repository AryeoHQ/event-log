# Event Log

The Event Log package makes a permanent, verifiable record of domain events. It
then sends each event to the parties that must receive it. This document gives
the full picture. The other documents give the detail.

## What the package does

The package does three things:

1. It records an event when your application dispatches it. The record is signed,
   so you can find out later if someone changed it.
2. It sends the event to one or more transports (for example, a webhook or an MQTT
   broker). One event can go to many transports.
3. It keeps a durable status for every step of that work. If a step fails, the
   status shows it. No event is lost without a trace.

The package is built for failure. Queues run a job more than once. Workers stop in
the middle of work. The design expects this and stays correct.

## The four tiers

An event moves through four tiers. Each tier is an Eloquent model with its own
`status` column. Each tier creates the tier below it.

```
Event
  │
  ▼
Log ──────────▶ Relay ──────────▶ Delivery ──────────▶ DeliveryAttempt
   one per        one per            one per               one per
   event          transport          recipient             send
```

| Tier | Question it answers |
|---|---|
| **Log** | What happened? (the signed record of the event) |
| **Relay** | Where must it go? (one per transport that wants the event) |
| **Delivery** | Who must receive it? (one per recipient, with the payload version they want) |
| **DeliveryAttempt** | Did the send work? (one per try, with the result) |

A `Log` makes a `Relay` for each transport. A `Relay` makes a `Delivery` for each
recipient. A `Delivery` makes a `DeliveryAttempt` for each try. The send happens
at the `DeliveryAttempt`.

## How a tier moves

Each tier is a state machine. A tier starts at `Pending`. A listener locks it,
which moves it to `Locked`. The lock step then starts the process step. The
process step does the tier's real work — it creates the children below, or (at the
last tier) it sends the message. When the work is done, the tier moves to a final
status.

The same shape repeats at every tier: **create → lock → process → children**. The
child's creation starts the same shape one tier down.

## How the package survives failure

Three mechanisms protect the pipeline. Each document explains one part.

- **Idempotent creation.** A step that creates children uses `firstOrCreate`. If
  the step runs twice, the second run finds the rows the first run made. It does
  not make duplicates.
- **One worker per record.** The process step holds a lock keyed to its record.
  Two workers cannot process the same record at the same time. The loser stops.
- **The watchdog.** A scheduled sweep finds records that are stuck in `Pending` or
  `Locked` past a grace period. It moves them to `Failed`, so they become visible.

`Failed` is not a defect. `Failed` is the designed place for a human to step in.
The true defect is a record that never reaches `Failed` — the watchdog exists to
prevent that.

## Where to read next

| Document | Subject |
|---|---|
| [architecture.md](architecture.md) | The models, tables, columns, indexes, and the recording path |
| [lifecycle.md](lifecycle.md) | How the create → lock → process cascade runs, and the watchdog |
| [state-machines.md](state-machines.md) | The status states, the transitions, and each trigger |
| [tooling.md](tooling.md) | The PHPStan rules that hold the contracts in place |

The namespace root is `Support\Events\Log\`, under `src/Support/Events/Log/`.
