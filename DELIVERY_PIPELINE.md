## Plan: Wire Up the Destination → Delivery Pipeline

`RecordDeliveries::handle()` is a stub, but it can't be implemented alone. The pieces it depends on are also incomplete or missing. This plan covers everything needed to make the full chain work: event gets recorded → destinations get created with the right processor → deliveries get created → jobs get dispatched.

**What's broken and why:**

- **`Manager` isn't in the container.** Nothing registers it as a singleton, so no action can resolve it.
- **`RecordDestination` hardcodes `destination_processor` to `''`.** It needs to look up the `Provider` via `Manager` and store `$provider->destinationProcessor`.
- **`DestinationProcessor` and `DeliveryProcessor` are empty interfaces.** No methods — there's nothing to call. `DestinationProcessor` needs a method to return what deliveries should be created for a given destination. `DeliveryProcessor` needs a method to actually execute a delivery.
- **`RecordDeliveries::handle()` is empty.** Once the above is fixed, it needs to: resolve the `DestinationProcessor` via the relationship tree → call it to get delivery data → dispatch a `RecordDelivery` action for each.
- **`RecordDelivery` action doesn't exist yet.** Needs to be created — receives delivery data, creates a `Delivery` record, dispatches `ProcessDelivery`. Each delivery gets its own queued job for retry granularity.

**Steps**

### Phase 1: Foundation (must come first)

- [x] Register `Manager` as a singleton in `EventLogServiceProvider::register()` — so actions can resolve it from the container.
- [x] Add a `belongsTo` relationship from `Destination` to `Log` — so the relationship tree can be traversed to resolve the `Provider`.

### Phase 2: Define processor contracts

- [x] Add a method to `DestinationProcessor` — returns the delivery data that should be created for a given destination (e.g. payloads, targets). Does not create records itself. Called by `RecordDeliveries`.
- [x] Add a method to `DeliveryProcessor` — actually executes a delivery (sends the webhook, hits an API, etc.). Called by `ProcessDelivery`. The result is recorded as an `Attempt`.

### Phase 3: Fix RecordDestination (*depends on Phase 1*)

- [x] Inject/resolve `Manager` in `RecordDestination::handle()` — use `app(Manager::class)`.
- [x] Look up the `Provider` for each `Destinationable` interface — `Manager::getProvider($destinationable)`. Store `$provider->destinationProcessor` in the `destination_processor` column.

### Phase 4: Implement RecordDeliveries (*depends on Phase 1, Phase 2*)

- [x] In `RecordDeliveries::handle()`: traverse the relationship tree (`Destination` → `Log` → deserialize event → inspect interfaces) to find the matching `Destinationable` → resolve the `Provider` via `Manager` → instantiate the `DestinationProcessor` from `$provider->destinationProcessor` → call it to get delivery data → dispatch `RecordDelivery` for each.

### Phase 5: Create RecordDelivery (*depends on Phase 4*)

- [x] Create `RecordDelivery` action (singular) — receives delivery data + `Destination`, creates a `Delivery` record, dispatches `ProcessDelivery`. Each delivery is its own queued job for retry granularity.

**Relevant files**
- `src/Support/Events/Providers/EventLogServiceProvider.php` — register `Manager` singleton
- `src/Support/Events/Manager.php` — already done, just needs to be in the container
- `src/Support/Events/Contracts/DestinationProcessor.php` — add method that returns delivery data
- `src/Support/Events/Contracts/DeliveryProcessor.php` — add delivery execution method
- `src/Support/Events/Destinations/Entities/Destination.php` — add `belongsTo` relationship to `Log`
- `src/Support/Events/Actions/RecordDestination.php` — resolve Manager, look up provider, store processor
- `src/Support/Events/Actions/RecordDeliveries.php` — full implementation
- `src/Support/Events/Actions/RecordDelivery.php` — new action (singular), creates Delivery + dispatches ProcessDelivery

**Decisions made**
- **`DestinationProcessor`** returns delivery data (payloads, targets, etc.) — does not create records itself. Called by `RecordDeliveries`.
- **`DeliveryProcessor`** actually executes a delivery (sends the webhook, hits an API, etc.). Called by `ProcessDelivery`. Results are recorded as `Attempt` records.
- **`RecordDelivery`** (new, singular) action dispatched per delivery for retry granularity. Creates the `Delivery` record and dispatches `ProcessDelivery`.
- **No `destinationable` column.** The `Destinationable` interface is resolved by traversing the relationship tree: `Destination` → `Log` → deserialize event → inspect interfaces.

**Further Considerations**
1. `ProcessDelivery::handle()` is also a stub (eval item 7). This plan doesn't cover it — it's a separate piece that uses the `DeliveryProcessor` to execute deliveries and record `Attempt` records.
