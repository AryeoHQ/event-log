# Plan: Migrate ForEntity/EntityDriven from entities to event-log

## TL;DR

Move `ForEntity`, `EntityDriven`, `SerializesModels`, `DisablesSerializesModels`, and all supporting files (attributes, exceptions, embedded tests, PHPStan rules, Rector rules, test fixtures, shared test utilities) from the `entities` package into `event-log`. Rename: `ForEntity` → absorbed into `Recordable`, `EntityDriven` → `SupportsEventLog`. Remove the `aryeo/entities` dependency from event-log. Remove all moved code from entities. Update the entities `MakeEvent` stub and Provider.

## Mapping

| entities (source)                                    | event-log (target)                                       | Change     |
|------------------------------------------------------|----------------------------------------------------------|------------|
| `ForEntity` interface                                | Absorbed into `Recordable`                               | Merge      |
| `EntityDriven` trait                                 | `SupportsEventLog` trait                                 | Rename     |
| `EntityDrivenTest.php` (embedded)                    | `SupportsEventLogTest.php`                               | Move+rename|
| `SerializesModels` concern                           | `SerializesModels` (new namespace)                       | Move       |
| `SerializesModelsTest.php` (embedded)                | `SerializesModelsTest.php` (new namespace)               | Move       |
| `DisablesSerializesModels` mixin                     | `DisablesSerializesModels` (new namespace)               | Move       |
| `DisablesSerializesModelsTest.php` (embedded)        | `DisablesSerializesModelsTest.php` (new namespace)       | Move       |
| `Alias` attribute                                    | `Alias` (new namespace)                                  | Move       |
| `AliasTest.php` (embedded)                           | `AliasTest.php` (new namespace)                          | Move       |
| `AliasMissing` exception                             | `AliasMissing` (new namespace)                           | Move       |
| `AliasMissingTest.php` (embedded)                    | `AliasMissingTest.php` (new namespace)                   | Move       |
| `ForEntityTestCases` trait                           | `RecordableTestCases`                                    | Move+rename|
| `TestsForEntity` interface                           | `TestsRecordable`                                        | Move+rename|
| PHPStan: `ForEntityMustHaveAlias`                    | `RecordableMustHaveAlias`                                | Move+rename|
| PHPStan: `ForEntityMustHaveEntityDriven`             | `RecordableMustHaveSupportsEventLog`                     | Move+rename|
| PHPStan: `EntityDrivenMustUseSerializesModels`       | `SupportsEventLogMustUseSerializesModels`                | Move+rename|
| Rector: `ForEntityMustHaveAlias`                     | `RecordableMustHaveAlias`                                | Move+rename|
| Rector configured-rules: `ForEntity → EntityDriven`  | `Recordable → SupportsEventLog`                          | Update     |
| `Entity` + `AsEntity` on event-log models            | Removed                                                  | Delete     |
| entities Provider mixin registration                 | Moved to event-log Provider                              | Move       |

## Steps

### Phase 1: Create new source files in event-log

- [x] Create `src/Support/Events/Log/Attributes/Alias.php` — copy from entities `Attributes/Alias.php`, namespace `Support\Events\Log\Attributes`
- [x] Create `src/Support/Events/Log/Attributes/AliasTest.php` — copy from entities, namespace `Support\Events\Log\Attributes`, update `Alias` import
- [x] Create `src/Support/Events/Log/Attributes/Exceptions/AliasMissing.php` — copy from entities, namespace `Support\Events\Log\Attributes\Exceptions`
- [x] Create `src/Support/Events/Log/Attributes/Exceptions/AliasMissingTest.php` — copy from entities, namespace `Support\Events\Log\Attributes\Exceptions`, update import
- [x] Create `src/Support/Events/Log/Concerns/SerializesModels.php` — copy from entities, namespace `Support\Events\Log\Concerns`, update `DisablesSerializesModels` import to `Support\Events\Log\Dispatcher\Mixins\DisablesSerializesModels`
- [x] Create `src/Support/Events/Log/Concerns/SerializesModelsTest.php` — copy from entities, update namespace + all fixture imports (use Article fixtures instead of Post fixtures)
- [x] Create `src/Support/Events/Log/Concerns/SupportsEventLog.php` — copy from `EntityDriven`, rename trait to `SupportsEventLog`, update imports for new `Alias`/`AliasMissing`/`SerializesModels` locations
- [x] Create `src/Support/Events/Log/Dispatcher/Mixins/DisablesSerializesModels.php` — copy from entities, namespace `Support\Events\Log\Dispatcher\Mixins`
- [x] Create `src/Support/Events/Log/Dispatcher/Mixins/DisablesSerializesModelsTest.php` — copy from entities, update namespace + fixture imports (use Article fixtures instead of Post)

### Phase 2: Update contracts and Provider in event-log

- [x] Update `src/Support/Events/Log/Contracts/Recordable.php` — remove `extends ForEntity`, add `entity`, `alias`, `uniqueAlias` properties directly (from ForEntity definition)
- [x] Update `src/Support/Events/Log/Contracts/RecordableAfterCommit.php` — remove ForEntity import, extend standalone Recordable
- [x] Update `src/Support/Events/Log/Providers/Provider.php` — add `DisablesSerializesModels` mixin registration in `boot()`: `Dispatcher::mixin(new DisablesSerializesModels)`

### Phase 3: Update all ~60 event classes in event-log

- [x] All events in `src/Support/Events/Log/Logs/Events/*.php` — `EntityDriven` → `SupportsEventLog` (use trait), old `Alias` import → `Support\Events\Log\Attributes\Alias`, remove any `ForEntity` imports
- [x] All events in `src/Support/Events/Log/Deliveries/Events/*.php` — same changes
- [x] All events in `src/Support/Events/Log/Destinations/Events/*.php` — same changes
- [x] All events in `src/Support/Events/Log/Attempts/Events/*.php` — same changes

### Phase 4: Update models in event-log

- [x] Remove `implements Entity`, `use AsEntity`, and their imports from: `Log.php`, `Destination.php`, `Delivery.php`, `Attempt.php`
- [x] Update `Log.php` `setEntityAttribute` — accept `Model` instead of `Entity`

### Phase 5: Create tooling rules in event-log

- [x] Create `src/Tooling/EventLog/PhpStan/RecordableMustHaveAlias.php` + test — adapted from entities' `ForEntityMustHaveAlias`, references `Recordable` interface, new `Alias` attribute
- [x] Create `src/Tooling/EventLog/PhpStan/RecordableMustHaveSupportsEventLog.php` + test — adapted from `ForEntityMustHaveEntityDriven`, references `Recordable` and `SupportsEventLog`
- [x] Create `src/Tooling/EventLog/PhpStan/SupportsEventLogMustUseSerializesModels.php` + test — adapted from `EntityDrivenMustUseSerializesModels`, references `SupportsEventLog` and `SerializesModels`
- [x] Create `tooling/phpstan/rules.neon` — register the 3 rules (entities has this, event-log doesn't yet)
- [x] Update `tooling/rector/configured-rules.php` — add `Recordable → SupportsEventLog` mapping
- [x] Create/update `tooling/rector/rules.php` — add `RecordableMustHaveAlias`

### Phase 6: Create/update test fixtures and shared test utilities in event-log

**Existing fixtures to update:**
- [x] Update `tests/Fixtures/Support/Entities/Articles/Events/Created.php` — `SupportsEventLog`, new `Alias` namespace, remove ForEntity/EntityDriven imports
- [x] Update `tests/Fixtures/Support/Entities/Articles/Article.php` — remove `implements Entity` and Entity import

**Serialization test fixtures to create (for SerializesModelsTest/DisablesSerializesModelsTest):**
- [x] Create `tests/Fixtures/Support/Entities/Articles/Events/DisablesModelSerialization.php` — same pattern as Created but with alias `article.disables_model_serialization`
- [x] Create `tests/Fixtures/Support/Entities/Articles/Events/DisablesModelSerializationThroughInterface.php` — same but also implements `\Stringable`

**Tooling test fixtures to create:**
- [x] Create fixtures for PHPStan/Rector rule tests: `ValidRecordableEvent.php`, `RecordableWithoutAlias.php`, `RecordableWithoutSupportsEventLog.php`, `ClassWithoutSupportsEventLog.php`

**Shared test utilities to create:**
- [x] Create `src/Support/Events/Log/Contracts/RecordableTestCases.php` — trait migrated from `ForEntityTestCases`, test assertions for `alias` and `uniqueAlias`, references `Recordable`
- [x] Create `tests/Support/Events/Log/Contracts/TestsRecordable.php` — interface migrated from `TestsForEntity`, requires `public Recordable $event { get; }`
- [x] Create `src/Support/Events/Log/Concerns/SupportsEventLogTest.php` — embedded test migrated from `EntityDrivenTest`, uses `RecordableTestCases`/`TestsRecordable`, references new namespaces

**Update all remaining Article event fixtures** (Trashed, Saving, Updated, etc.) with SupportsEventLog + new Alias import

### Phase 7: Remove entities dependency from event-log

- [x] Remove `"aryeo/entities"` from `composer.json` require
- [x] Remove entities repository entry from `composer.json` repositories (if present)

### Phase 8: Remove ForEntity/EntityDriven infrastructure from entities

**Source files to delete (12 files):**
- [x] `src/Support/Entities/Events/Contracts/ForEntity.php`
- [x] `src/Support/Entities/Events/Contracts/ForEntityTestCases.php`
- [x] `src/Support/Entities/Events/Provides/EntityDriven.php`
- [x] `src/Support/Entities/Events/Provides/EntityDrivenTest.php`
- [x] `src/Support/Entities/Events/Concerns/SerializesModels.php`
- [x] `src/Support/Entities/Events/Concerns/SerializesModelsTest.php`
- [x] `src/Support/Entities/Events/Dispatcher/Mixins/DisablesSerializesModels.php`
- [x] `src/Support/Entities/Events/Dispatcher/Mixins/DisablesSerializesModelsTest.php`
- [x] `src/Support/Entities/Events/Attributes/Alias.php`
- [x] `src/Support/Entities/Events/Attributes/AliasTest.php`
- [x] `src/Support/Entities/Events/Attributes/Exceptions/AliasMissing.php`
- [x] `src/Support/Entities/Events/Attributes/Exceptions/AliasMissingTest.php`

**Test support to delete:**
- [x] `tests/Support/Entities/Events/Contracts/TestsForEntity.php`

**PHPStan rules to delete:**
- [x] `src/Tooling/Entities/PhpStan/ForEntityMustHaveAlias.php` + test
- [x] `src/Tooling/Entities/PhpStan/ForEntityMustHaveEntityDriven.php` + test
- [x] `src/Tooling/Entities/PhpStan/EntityDrivenMustUseSerializesModels.php` + test
- [x] Update `tooling/phpstan/rules.neon` — remove the 3 rule registrations + `ignoreErrors` for EntityDriven/SerializesModels

**Rector rules to delete:**
- [x] `src/Tooling/Entities/Rector/ForEntityMustHaveAlias.php` + test
- [x] Update `tooling/rector/configured-rules.php` — remove `ForEntity => EntityDriven`
- [x] Update `tooling/rector/rules.php` — remove `ForEntityMustHaveAlias`

**Test fixtures to delete:**
- [x] `tests/Fixtures/Tooling/Entities/ValidEntityEvent.php`
- [x] `tests/Fixtures/Tooling/Entities/ForEntityWithoutAlias.php`
- [x] `tests/Fixtures/Tooling/Entities/ForEntityWithoutEntityDriven.php`
- [x] `tests/Fixtures/Tooling/Entities/ClassWithoutEntityDriven.php`
- [x] `tests/Fixtures/Support/Posts/Events/Created.php`
- [x] `tests/Fixtures/Support/Posts/Events/DisablesModelSerialization.php`
- [x] `tests/Fixtures/Support/Posts/Events/DisablesModelSerializationThroughInterface.php`
- [x] `tests/Fixtures/Support/Posts/Post.php`
- [x] `tests/Fixtures/Support/Posts/Factory.php`

**Provider to update:**
- [x] `src/Support/Entities/Providers/Provider.php` — remove `DisablesSerializesModels` import and `Dispatcher::mixin(...)` from `boot()`

**Stub to update:**
- [x] `src/Support/Entities/Models/Console/Commands/stubs/event/event.stub` — remove ForEntity, EntityDriven, Alias references

### Phase 9: Verification (both repos)

**event-log:**
- [x] `composer update`
- [x] `./vendor/bin/phpunit`
- [ ] `./vendor/bin/testbench tooling:phpstan`
- [ ] `./vendor/bin/testbench tooling:pint`
- [ ] `./vendor/bin/testbench tooling:rector --dry-run`

**entities:**
- [ ] `composer update` (if needed after removing event-related code)
- [ ] `./vendor/bin/phpunit`
- [ ] `./vendor/bin/testbench tooling:phpstan`
- [ ] `./vendor/bin/testbench tooling:pint`
- [ ] `./vendor/bin/testbench tooling:rector --dry-run`

## Decisions

- **Recordable becomes standalone** — absorbs ForEntity's properties (entity, alias, uniqueAlias)
- **EntityDriven → SupportsEventLog** — new name reflects event-log context
- **Entity + AsEntity removed** — not needed for event-log models; Log.setEntityAttribute accepts Model instead
- **entities dependency fully removed** — event-log becomes self-contained
- **Full removal from entities** — all ForEntity/EntityDriven/SerializesModels/DisablesSerializesModels code, tooling rules, test fixtures, and shared test utilities deleted
- **MakeEvent stub updated** — generates plain event classes without ForEntity/EntityDriven
- **DisablesSerializesModels mixin registration** — moves from entities Provider to event-log Provider
- **Post fixtures deleted from entities** — only used by migrated tests; event-log uses existing Article fixtures
- **TestsGeneratesForEntity stays in entities** — references Entity (model interface), not ForEntity (event interface); unrelated to migration
- **Embedded test files (AliasTest, AliasMissingTest, SerializesModelsTest, DisablesSerializesModelsTest, EntityDrivenTest)** — all 5 co-located test files migrated alongside their source files
