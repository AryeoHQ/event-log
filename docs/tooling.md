# Tooling

The package ships build-time tooling: PHPStan rules that enforce its contracts, and
a classmap collector that finds every event able to go out over a transport. A
misconfigured event is caught before it runs. The rules live in
`src/Tooling/EventLog/PhpStan/` and `tooling/phpstan/rules.neon` registers them.
For the runtime pipeline, start at [README.md](README.md).

## The invariant pattern

Several rules come in pairs. A pair keeps a contract and its provider (a trait or
an attribute) in lockstep:

- **class → attribute** — a class that plays a role must carry the marking
  attribute.
- **interface ⇄ trait** — a class that implements a contract must use its provider
  trait, and the reverse.

So you cannot, for example, implement `Recordable` without `HasLoggable`, or use
`CollectsEnvelopes` without implementing `NeedsEnvelopes`. PHPStan flags either
half.

## Rules

### Recording

| Rule | Target | Enforces | Identifier |
|---|---|---|---|
| `RecordableMustHaveAlias` | class | A `Recordable` carries `#[Alias]` | `eventLog.Recordable.Alias.required` |
| `RecordableMustUseHasLoggable` | class | A `Recordable` uses the `HasLoggable` trait | `eventLog.Recordable.HasLoggable.required` |
| `HasLoggableMustImplementRecordable` | class | Anything that uses `HasLoggable` implements `Recordable` | `eventLog.HasLoggable.Recordable.required` |
| `HasLoggableMustUseSerializesModels` | trait | The `HasLoggable` trait uses the package `SerializesModels` | `eventLog.HasLoggable.SerializesModels.required` |
| `RecordableMustNotOverrideSerialization` | class | A `Recordable` does not define `__sleep`/`__wakeup`/`__serialize`/`__unserialize` (which would break the signed blob) | `eventLog.Recordable.SerializationMethods.forbidden` |
| `RecordableMustHaveIdentifiesLoggable` | class | A `Recordable` has at least one `#[IdentifiesLoggable]` property | `eventLog.Recordable.IdentifiesLoggable.required` |
| `RecordableMustHaveSingleIdentifiesLoggable` | class | A `Recordable` has at most one `#[IdentifiesLoggable]` property | `eventLog.Recordable.IdentifiesLoggable.multiple` |
| `IdentifiesLoggableMustBeLoggableModel` | class | The `#[IdentifiesLoggable]` property is typed as `Model & Loggable` | `eventLog.Recordable.IdentifiesLoggable.type` |

### Relays and transports

| Rule | Target | Enforces | Identifier |
|---|---|---|---|
| `TransportMustHaveDispatches` | interface | A `Transport` sub-interface carries `#[Dispatches]` (the runtime `NotDefined` is the backstop) | `eventLog.Relayable.Dispatches.required` |
| `TransportMustUseHasRelays` | class | A class that implements a `Transport` uses the `HasRelays` trait | `eventLog.Transport.HasRelays.required` |
| `HasRelaysMustImplementTransport` | class | Anything that uses `HasRelays` implements a `Transport` | `eventLog.HasRelays.Transport.required` |
| `TransportMustHaveUniqueAlias` | collected | No two `Transport` implementers share an `#[Alias]` value | `eventLog.Transport.Alias.name.duplicate` |
| `DispatchesCollectingMustImplementNeedsEnvelopes` | interface | The `collecting` class in `#[Dispatches]` implements `NeedsEnvelopes` | `eventLog.Dispatches.collecting.invalid` |
| `DispatchesSendingMustImplementNeedsSent` | interface | The `sending` class in `#[Dispatches]` implements `NeedsSent` | `eventLog.Dispatches.sending.invalid` |
| `NeedsEnvelopesMustUseCollectsEnvelopes` | class | A `NeedsEnvelopes` class uses the `CollectsEnvelopes` trait | `eventLog.NeedsEnvelopes.CollectsEnvelopes.required` |
| `CollectsEnvelopesMustImplementNeedsEnvelopes` | class | Anything that uses `CollectsEnvelopes` implements `NeedsEnvelopes` | `eventLog.CollectsEnvelopes.NeedsEnvelopes.required` |
| `NeedsSentMustUseRecordsResult` | class | A `NeedsSent` class uses the `RecordsResult` trait | `eventLog.NeedsSent.RecordsResult.required` |
| `RecordsResultMustImplementNeedsSent` | class | Anything that uses `RecordsResult` implements `NeedsSent` | `eventLog.RecordsResult.NeedsSent.required` |

### Swapped models

A model can be swapped for a consumer subclass, and PHP does not inherit
attributes. A subclass that omits one falls back to the framework default — on the
four pipeline models that means the builder loses `stuck()`, so the watchdog
sweeps nothing and nothing errors. Declaring the attribute is not enough either,
since it can name a framework class, so each attribute gets a presence rule and an
ancestry rule.

Each rule checks all five models. The error message names which model matched.

| Rule | Target | Enforces | Identifier |
|---|---|---|---|
| `SwappableSubclassMustDeclareUseFactory` | collected | The subclass carries `#[UseFactory]` | `eventLog.Swappable.Subclass.UseFactory.required` |
| `SwappableSubclassMustDeclareUseEloquentBuilder` | collected | The subclass carries `#[UseEloquentBuilder]` | `eventLog.Swappable.Subclass.UseEloquentBuilder.required` |
| `SwappableSubclassMustDeclareCollectedBy` | collected | The subclass carries `#[CollectedBy]` | `eventLog.Swappable.Subclass.CollectedBy.required` |
| `SwappableSubclassFactoryMustExtendFactory` | collected | The named factory extends ours | `eventLog.Swappable.Subclass.Factory.invalid` |
| `SwappableSubclassBuilderMustExtendBuilder` | collected | The named builder extends ours | `eventLog.Swappable.Subclass.Builder.invalid` |
| `SwappableSubclassCollectionMustExtendCollection` | collected | The named collection extends ours | `eventLog.Swappable.Subclass.Collection.invalid` |

The `Collectors\SwappableSubclasses` PHPStan collector finds every subclass of a
swappable model as PHPStan walks the codebase, and reflects the base model's
expected factory, builder, and collection. The six rules above read from that
collector — they don't need the container or the classmap cache.

The three ancestry rules are not the last word. The `Swapper` checks the same
thing at runtime — `newFactory()`, `newEloquentBuilder()`, and `newCollection()`
each call `Swapper::validateAttribute()`, which throws
`Swapper\Exceptions\Invalid` when the
attribute names something outside our hierarchy, or `MissingAttribute` when the
subclass left the attribute off. So the rules are the early warning, and a
baselined or unrun PHPStan still cannot get a foreign class past them.

There is no rule for `$table`, `$primaryKey`, `$keyType`, or `$incrementing`.
Those are `final` on every model, so PHP rejects a subclass that redeclares one
before any of it runs — a subclass swaps the class, not the schema.

`initializeTraits()` is `final` too. That is where `Swapper::consolidate()`
merges `$casts`, `$dispatchesEvents`, `$fillable` and five more back into the
subclass.
It runs there because it is the one hook that fires after every trait initializer.
A subclass that overrode it and forgot `parent::` would quietly lose our casts and
our events, so PHP stops it instead.

There is no rule for `$dispatchesEvents`. The `Swapper` merges it, so redeclaring
it is supported rather than forbidden (see
[architecture.md](architecture.md#swapping-a-model)).

## PHPStan collectors

PHPStan rules see one node at a time. When a rule needs to answer a question that
spans files, a collector gathers data across the codebase first, and the rule reads
the collected set at the end.

`Collectors\TransportAliases` gathers the alias, class, and line of every
`Transport` implementer. `TransportMustHaveUniqueAlias` reads the collected set and
reports each file that shares an alias with another.

`Collectors\SwappableSubclasses` finds every subclass of a swappable model and
reflects both the subclass's declared attributes and the base model's expected
factory, builder, and collection. The six swapped-model rules read from it.

## Classmap collector

`Composer\ClassMap\Collectors\Transports` finds every concrete class that
implements a `Transport`. The provider tags it `tooling.classmap.collectors`, and
composer's post-autoload-dump hook warms the cache.

`Transportables\Discovery` reads that cache to build the catalog. So the catalog is
only as fresh as the last `composer install` — which is why
`event-log:transportables:synchronize` runs at deploy, after composer. See
[architecture.md](architecture.md#the-transportable-catalog).

## Reflection extension

`Extensions\DisablesSerializesModels` is a `MethodsClassReflectionExtension`, not a
rule. It teaches PHPStan about the `withoutSerializesModels()` macro. The
`Support\Events\Dispatcher\Mixins\DisablesSerializesModels` mixin adds that macro
to Laravel's `Dispatcher` and the `Event` facade. Without the extension, static
analysis would report the macro as an undefined method.

## Ignored errors

`rules.neon` ignores `match.alwaysFalse` scoped to
`Concerns/SerializesModels.php`. It is an artifact of wrapping Laravel's
`SerializesModels`, and it is expected.
