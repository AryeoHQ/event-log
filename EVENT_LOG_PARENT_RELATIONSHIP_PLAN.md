# Plan: Event Log Parent Relationship (Event Bubbling via Query)

## TL;DR

Allow parent models to include event logs from their children by defining an `eventLogs()` relationship that queries the existing `event_logs` table using subqueries derived from the parent's own Eloquent relationships. No schema changes, no trait — just a custom `Relation` class that the consumer instantiates directly in their relationship method, following standard Laravel patterns.

## Consumer API

```php
use Support\Events\Log\Logs\Relations\EventLogs;

class Company extends Model {
    // Company includes its own events + events from jobs + events from jobs' appointments
    public function eventLogs(): EventLogs {
        return EventLogs::make($this, 'jobs', 'jobs.appointments');
    }
}

class Job extends Model {
    // Job includes its own events + events from appointments
    public function eventLogs(): EventLogs {
        return EventLogs::make($this, 'appointments');
    }
}

class Appointment extends Model {
    // Appointment only includes its own events
    public function eventLogs(): EventLogs {
        return EventLogs::make($this);
    }
}
```

Both `EventLogs::make($this, ...)` and `new EventLogs($this, [...])` are supported.

The generated SQL for `$company->eventLogs` would be:
```sql
SELECT * FROM event_logs WHERE
  (entity_type = 'company' AND entity_id = ?)
  OR (entity_type = 'job' AND entity_id IN (
    SELECT id FROM jobs WHERE company_id = ?
  ))
  OR (entity_type = 'appointment' AND entity_id IN (
    SELECT id FROM appointments WHERE job_id IN (
      SELECT id FROM jobs WHERE company_id = ?
    )
  ))
```

## Approach: Custom Relation

A custom `Relation` class (`EventLogs`) extending `Illuminate\Database\Eloquent\Relations\Relation`. This is the right tool because:
- It integrates naturally with Eloquent (`$model->eventLogs`, eager loading via `with('eventLogs')`, etc.)
- It supports `addConstraints()` for single-model loading and `addEagerConstraints()` for eager loading
- The query is purely against the existing `event_logs` table with no joins — just OR'd `WHERE` clauses with subqueries
- No trait needed — the consumer instantiates the Relation directly, just as they could with any built-in relation

### How the relationship paths resolve

Given `EventLogs::make($this, 'jobs', 'jobs.appointments')`, the relation resolves each dot-notated path by:

1. Walking the dot-notation path on the parent model to discover intermediate models (calling each relationship method to get the Relation instance, then inspecting the related model, foreign keys, and table)
2. Building a nested subquery for each segment to resolve the final entity IDs
3. Using `getMorphClass()` on the terminal model to get the `entity_type` value

For path `jobs.appointments`:
- Start from `Company`, call `$company->jobs()` → discovers `Job` model, gets foreign key `company_id`, table `jobs`
- Then call `(new Job)->appointments()` → discovers `Appointment` model, gets foreign key `job_id`, table `appointments`
- Terminal model `Appointment::getMorphClass()` → entity_type value
- Build: `entity_type = 'appointment' AND entity_id IN (SELECT id FROM appointments WHERE job_id IN (SELECT id FROM jobs WHERE company_id = ?))`

## Steps

### Phase 1: Core Relation

- [ ] **Create `EventLogs` relation class** at `src/Support/Events/Log/Logs/Relations/EventLogs.php`
   - Extends `Illuminate\Database\Eloquent\Relations\Relation`
   - Constructor: `__construct(Model $parent, array $through = [])` — internally creates `Log::query()` and calls `parent::__construct()`
   - Static factory: `make(Model $parent, string ...$through): static`
   - Stores the through-paths for later query building
   - Implements all abstract methods: `addConstraints()`, `addEagerConstraints()`, `initRelation()`, `match()`, `getResults()`

- [ ] **Implement `addConstraints()`** — single-model constraint
   - Starts with the parent's own morph clause: `(entity_type = X AND entity_id = ?)`
   - For each through-path, resolves the relationship chain and builds a nested `whereIn` subquery
   - Combines all with `orWhere` groups

- [ ] **Implement `addEagerConstraints()`** — bulk constraint for eager loading
   - Same logic as `addConstraints()` but uses `whereIn` for the parent's own IDs, and adjusts subqueries to filter by multiple parent IDs

- [ ] **Implement `initRelation()`** — initialize empty `Logs` collections on models before matching

- [ ] **Implement `match()`** — match eagerly loaded results back to parent models
   - For direct entity matches: straightforward (entity_type matches parent's morph class, entity_id matches parent key)
   - For through-path matches: two approaches to evaluate during implementation:
     - **Option A**: Include a computed `_parent_id` column via CASE/subquery in the SELECT, use it for matching, then strip it from results
     - **Option B**: Post-load, build a reverse-lookup map from the through-path subqueries
   - **Recommendation**: Prototype both; Option A is likely cleaner

- [ ] **Implement `getResults()`** — execute the constrained query and return the `Logs` collection

### Phase 2: Tests

- [ ] **Create test fixture models** — e.g., `Author`, `Publication` in `tests/Fixtures/` alongside existing `Article`, with `eventLogs()` relationships and through-paths configured, plus migrations for their tables

- [ ] **Test cases**:
   - [ ] Model with no through-paths returns only its own event logs
   - [ ] Model with one through-path includes child event logs
   - [ ] Model with nested through-path (e.g., `'authors.articles'`) includes grandchild event logs
   - [ ] Eager loading works (`Publication::with('eventLogs')->get()`)
   - [ ] Empty results return empty `Logs` collection
   - [ ] Through-path with non-existent relationship throws (developer error)

### Phase 3: Verification

- [ ] Run `./vendor/bin/phpunit`
- [ ] Run `./vendor/bin/testbench tooling:phpstan`
- [ ] Run `./vendor/bin/testbench tooling:pint`
- [ ] Run `./vendor/bin/testbench tooling:rector --dry-run`

## Relevant Files

- `src/Support/Events/Log/Logs/Relations/EventLogs.php` — **NEW**: custom Relation class (core of this feature)
- `src/Support/Events/Log/Logs/Entities/Log.php` — existing Log model (the relation's target); `entity()` MorphTo, `$table = 'event_logs'`
- `src/Support/Events/Log/Logs/Builder/Builder.php` — existing custom builder used by Log
- `src/Support/Events/Log/Logs/Collection/Logs.php` — existing custom collection returned by the relation
- `tests/Fixtures/Support/Entities/Articles/Article.php` — existing test fixture (reference for creating new fixtures)

## Decisions

- **No schema changes** — the existing `entity_type`/`entity_id` columns on `event_logs` are sufficient
- **No trait** — the consumer instantiates `EventLogs` directly in their relationship method via `EventLogs::make($this, ...)` or `new EventLogs($this, [...])`
- **No morphs/pivots** — purely query-time resolution via subqueries against existing relationships
- **Parent declares through-paths** — the child has no awareness of bubbling; the parent knows its own relationships and uses Laravel-standard dot notation
- **Custom Relation** — a first-class Eloquent relation for full ecosystem compatibility (eager loading, `withCount`, `has()`, etc.)
- **Excluded**: trait-based approaches, pivot table approaches, write-time record duplication

## Further Considerations

1. **Eager loading `match()` complexity**: Matching logs back to parent models during eager loading requires knowing which parent each log belongs to. The cleanest approach adds a computed column during the query. An alternative is a map built from the through-path subqueries post-load. We should prototype both during implementation.

2. **~~Should the parent's own logs always be included?~~** Yes — `$company->eventLogs` always includes Company's own logs in addition to the through-paths. The current model's logs are never excluded.

3. **~~Implicit intermediates~~**: Each through-path is explicit — `EventLogs::make($this, 'jobs.appointments')` only includes Appointment logs, not Job logs. To include both, you must specify both: `EventLogs::make($this, 'jobs', 'jobs.appointments')`. Intermediates are never implicitly included.
