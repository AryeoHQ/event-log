## Evaluation: Event Log Codebase vs Specification

**TL;DR:** The structural scaffolding is largely in place (~60-70% of the skeleton), but the core business logic pipeline is incomplete, several spec requirements are unimplemented, and there are zero tests.

---

### What's Implemented & Matches the Spec

| Area | Status | Notes |
|------|--------|-------|
| **Dispatcher decorator** | Mostly done | Implements `Contracts\Dispatcher` interface and delegates to wrapped dispatcher. Calls `RecordEvent::make()` on dispatch. |
| **ServiceProvider** | Done | Decorates `events` binding, merges config, loads migrations, publishes config. |
| **Config** | Done | `key_to_the_actor` / `key_to_the_subject` match spec. |
| **Contracts hierarchy** | Done | `Recordable` → `Destinationable` → `Webhookable`; `Destination`, `Provider`, `DestinationProcessor`, `DeliveryProcessor` all present with correct signatures. |
| **Manager** | Done | `register()` and `getProvider()` for mapping `Destinationable` → `Provider`. |
| **4 database tables** | Done | `event_logs`, `event_log_destinations`, `event_log_deliveries`, `event_log_delivery_attempts` — schema matches spec. |
| **4 Eloquent models** | Done | `Log`, `Destination`, `Delivery`, `Attempt` — all implement `Entity`, use `AsEntity`, `HasUuids`, with custom builders/collections/factories. |
| **RecordEvent action** | Partially done | Creates `Log` record, checks `Recordable`, calls `prepareDestinations`. |
| **RecordDestination action** | Partially done | Finds `Destinationable` interfaces via reflection, creates `Destination` records, dispatches `RecordDeliveries`. |

---

### Gaps & Discrepancies

#### 1. Dispatcher — approach differs from spec
- **Spec:** `extends \Illuminate\Events\Dispatcher` with property copying via `get_object_vars`.
- **Code:** Implements `\Illuminate\Contracts\Events\Dispatcher` interface and delegates via composition (decorator pattern).
- **Assessment:** The current approach is actually *cleaner* than the spec's — true decorator vs. inheritance-based copying. However, it means features like `getRawListeners()` or non-interface methods on the concrete `Dispatcher` won't be available unless `__call` delegates them (which it does). The ServiceProvider type-hints `BaseDispatcher` (concrete class) in the `extend` callback, which will break if Laravel's container resolves the interface instead, but this is likely fine with testbench.

#### ~~2. `Recordable` does not extend `ForEntity`~~ ✅ Resolved
- `Recordable` now extends `ForEntity`. Entity resolution in `RecordEvent` uses `$this->event->entity->getKey()` and `$this->event->entity->getMorphClass()`. Type field uses `$this->event->broadcastAs()`.
- **Deviation from spec:** `public string $name { get; }` removed from `Recordable`. The `EntityDriven` trait provides `$name` as `protected`. Use `broadcastAs()` publicly.

#### 3. `RecordEvent` — actor/subject resolution is incomplete
- **Spec:** `'actor' => Context::get(...)`, `'subject' => Context::get(...)` (single morph values)
- **Code:** Stores `actor_id` / `actor_type` / `subject_id` / `subject_type` (uuidMorphs), but only populates `*_id` from Context and hardcodes `*_type` to `null`.
- **Impact:** The morph type columns will always be null — no way to resolve the actor/subject *type* from Context as written. The spec also says "validate that context has the expected actor & subject values" — no validation exists.

#### ~~4. `RecordEvent` — missing `ShouldQueue`~~ ✅ Not an issue
- `Action` contract already extends `ShouldQueue`. All actions implement `Action`, so they are all queueable.

#### 5. `RecordDestination` — `destination_processor` is always empty string
- **Code at** `RecordDestination.php` line 33: `'destination_processor' => ''`
- **Spec:** Should look up the processor from the registered `Provider` via the `Manager`.
- **Impact:** Every `Destination` record will have an empty `destination_processor`, making downstream processing impossible.

#### 6. `RecordDeliveries::handle()` — completely empty (stub)
- `RecordDeliveries.php` line 24: Body is just comments.
- **Spec:** Should resolve the `DestinationProcessor` (via relationship tree: `Destination` → `Log` → deserialize event → inspect interfaces → `Manager` → `Provider`), call it to get delivery data, then dispatch a `RecordDelivery` action for each. `RecordDelivery` (singular, new) creates the `Delivery` record and dispatches `ProcessDelivery`. Per-delivery queued jobs give retry granularity.

#### 7. `ProcessDelivery::handle()` — completely empty (stub)
- `ProcessDelivery.php` line 23: Body is just a comment.
- **Spec:** Should use the `DeliveryProcessor` to actually execute the delivery (send webhook, hit API, etc.) and create `Attempt` records.

#### 8. No pruning strategy
- **Spec:** 30-day pruning on `event_logs`, `event_log_destinations`, `event_log_deliveries`, `event_log_delivery_attempts`.
- **Code:** None of the models use the `Prunable` or `MassPrunable` trait. No pruning configuration exists.

#### 9. No `EventLog::deleted()` cleanup
- **Spec:** `event_log_destinations.event_log_id` is "not constrained, cleaned up by `EventLog::deleted()` event."
- **Code:** No model event or observer on `Log` to cascade-delete destinations, deliveries, or attempts.

#### 10. Zero tests
- `tests/Support/` and `tests/Fixtures/` are empty directories.
- No feature or unit tests exist for any of the implemented functionality.

#### ~~11. Missing `id` in `$fillable` / UUID generation~~ ✅ Resolved
- All four models now use `HasUuids` trait. Manual `Str::uuid()` calls removed from actions. `id` removed from `$fillable`.

#### 12. Factories have empty definitions
- All four factories (`Log`, `Destination`, `Delivery`, `Attempt`) return `[]` from `definition()` — they won't produce usable records without overrides.

#### 13. ~~Missing Entity package dependency~~ ✅ Resolved / Missing State Machine dependency
- **Spec:** "take a dependency on the Entity, Actions, and State Machine packages."
- **Code:** `composer.json` now requires `aryeo/actions` and `aryeo/entities`. Still no dependency on a State Machine package. The `status` fields on destinations/deliveries/attempts are plain strings — no state machine integration.

---

### Priority Summary

#### P0 – Blocking
- [ ] `RecordDeliveries::handle()` — stub body (Medium)
- [ ] `ProcessDelivery::handle()` — stub body (Medium)
- [ ] `destination_processor` lookup from Manager/Provider in `RecordDestination` (Small)

#### P1 – Correctness
- [x] ~~`Recordable` should extend `ForEntity`; entity resolution in `RecordEvent` (Small-Medium)~~
- [ ] Actor/subject morph type resolution (not just id) (Small)
- [x] ~~Add `ShouldQueue` to actions per spec (Small) — `Action` contract already extends `ShouldQueue`~~
- [ ] Context validation for actor/subject (Small)

#### P2 – Required
- [ ] 30-day pruning via `Prunable`/`MassPrunable` traits (Small)
- [ ] `Log::deleted()` cascade cleanup for destinations (Small)
- [ ] State Machine integration for `status` fields (Medium, dependency)
- [x] ~~Entity package dependency in composer.json (Small)~~
- [ ] State Machine package dependency in composer.json (Small)

#### P3 – Quality
- [ ] Tests (zero exist) (Large)
- [ ] Factory definitions (Small)
