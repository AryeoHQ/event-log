## Plan: State Machine Integration for Event Log

**TL;DR:** Replace the Action-based pipeline with state machine Triggers. Every model gets a co-located Status enum (`Ready`, `Pending`, `Processed`, `Failed`) with two triggers: **Prepare** (Ready → Pending, dispatches Process) and **Process** (Pending → Processed, does the real work). **RecordEvent** is the sole Action — it creates the Log and kicks off `$log->status->prepare()->now()`. Everything downstream is triggers.

---

### States (uniform across all 4 models)

- **Ready** — record created, untouched. Reconciliation targets this.
- **Pending** — Prepare succeeded, Process was dispatched to the queue.
- **Processed** — Process succeeded, work is done.
- **Failed** — something went wrong.

### Triggers (same pattern per model)

| Trigger | Transition | Responsibility |
|---------|-----------|----------------|
| **Prepare** | Ready → Pending | Dispatch the Process trigger |
| **Process** | Pending → Processed | Do the actual work, create child records |
| **Fail** | Ready/Pending → Failed | Handle failures |

### Pipeline flow

```
Dispatcher intercepts Recordable event
  → RecordEvent::make($event)->now()
    → Log::create(Ready)
    → $log->status->prepare()->now()
      → Log Prepare [Ready → Pending]: dispatches Log Process
        → [queue] Log Process [Pending → Processed]:
          → creates Destinations (Ready) per Destinationable
          → $destination->status->prepare()->now() for each
            → Dest Prepare [Ready → Pending]: dispatches Dest Process
              → [queue] Dest Process [Pending → Processed]:
                → DestinationProcessor->deliveries()
                → creates Deliveries (Ready)
                → $delivery->status->prepare()->now() for each
                  → Del Prepare [Ready → Pending]: dispatches Del Process
                    → [queue] Del Process [Pending → Processed]:
                      → DeliveryProcessor->deliver()
                      → creates Attempt (Ready)
                      → $attempt->status->process()->now()
```

### Steps

#### Phase 1: Add status to Log (prerequisite)
- [x] Add `status` string column to `event_logs` migration
- [x] Add `status` to Log model `$fillable`, default attribute, cast

#### Phase 2: Create Status enums + events (parallel across all 4 domains)
- [x] Create `Logs/Status/Status.php` — `Ready`, `Pending`, `Processed`, `Failed` with `#[Events]` and `#[Transition]`
- [x] Create `Destinations/Status/Status.php` — `Ready`, `Pending`, `Processed`, `Failed` with `#[Events]` and `#[Transition]`
- [x] Create `Deliveries/Status/Status.php` — `Ready`, `Pending`, `Processed`, `Failed` with `#[Events]` and `#[Transition]`
- [x] Create `Attempts/Status/Status.php` — `Ready`, `Pending`, `Processed`, `Failed` with `#[Events]` and `#[Transition]`
- [x] Create before/after event classes per case (co-located in `Status/Events/`) — `Preparing`/`Prepared`, `Processing`/`Processed`, `Failing`/`Failed`

#### Phase 3: Create Triggers (depends on Phase 2)
- [x] `Logs/Status/Triggers/Process.php` — creates Destinations per Destinationable, calls their Prepare (absorbs RecordDestination)
- [x] `Destinations/Status/Triggers/Process.php` — calls DestinationProcessor, creates Deliveries, calls their Prepare (absorbs RecordDeliveries + RecordDelivery)
- [x] `Deliveries/Status/Triggers/Process.php` — calls DeliveryProcessor, creates Attempt (absorbs ProcessDelivery)
- [x] `Attempts/Status/Triggers/Process.php` — records result
- [x] `{all}/Status/Triggers/Prepare.php` — thin, dispatches Process
- [x] `{all}/Status/Triggers/Fail.php` — transitions to Failed

#### Phase 4: Wire up models (depends on Phase 2)
- [x] Update all 4 models: add Status cast, default attribute

#### Phase 5: Rewire pipeline (depends on Phase 3, 4)
- [x] Update RecordEvent: replace `prepareDestinations()` with `$log->status->prepare()->now()`
- [x] Update Dispatcher: `->now()` instead of `->dispatch()` (already done)
- [x] Delete `RecordDestination`, `RecordDeliveries`, `RecordDelivery`, `ProcessDelivery`, `Jobs/`

#### Phase 6: Verification
- [ ] `composer dump-autoload`, run tests

### Relevant files

#### Create (all under `src/Support/Events/Log/`)
- `{Logs,Destinations,Deliveries,Attempts}/Status/Status.php`
- `{Logs,Destinations,Deliveries,Attempts}/Status/Events/{Preparing,Prepared,Processing,Processed,Failing,Failed}.php`
- `{Logs,Destinations,Deliveries,Attempts}/Status/Triggers/{Prepare,Process,Fail}.php`

#### Modify
- `Logs/Entities/Log.php` — cast + default + fillable + status column in migration
- `Destinations/Entities/Destination.php` — cast + default
- `Deliveries/Entities/Delivery.php` — cast + default
- `Attempts/Entities/Attempt.php` — cast + default
- `Actions/RecordEvent.php` — replace prepareDestinations with prepare trigger
- `Dispatcher.php` — `->now()` instead of `->dispatch()`

#### Delete
- `Actions/RecordDestination.php`, `RecordDeliveries.php`, `RecordDelivery.php`, `ProcessDelivery.php`
- `Jobs/` directory

### Decisions
- RecordEvent is the sole Action — creates the Log (no model exists to target)
- Everything else is Triggers — uniform 4-state lifecycle per model
- Prepare trigger is thin (dispatch Process) — gives Pending as proof of queue dispatch
- Process trigger holds the real work — different per model
- Status enums are separate per model (same cases, different trigger bindings)
- Event naming follows package convention: gerund/past-participle (`Preparing`/`Prepared`)
