# Tooling

The package ships custom PHPStan rules. They enforce its contracts at
static-analysis time, so a misconfigured event or transport is caught before it
runs. The rules live in `src/Tooling/EventLog/PhpStan/`. `tooling/phpstan/rules.neon`
registers them. For the runtime pipeline, start at [README.md](README.md).

## The invariant pattern

Most rules come in pairs. A pair keeps a contract and its provider (a trait or an
attribute) in lockstep. The rule enforces both directions:

- **class ⇄ attribute** — a class that plays a role must carry the marking
  attribute, and the reverse.
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
| `DispatchesCollectingMustImplementNeedsEnvelopes` | interface | The `collecting` class in `#[Dispatches]` implements `NeedsEnvelopes` | `eventLog.Dispatches.collecting.invalid` |
| `DispatchesSendingMustImplementNeedsSent` | interface | The `sending` class in `#[Dispatches]` implements `NeedsSent` | `eventLog.Dispatches.sending.invalid` |
| `NeedsEnvelopesMustUseCollectsEnvelopes` | class | A `NeedsEnvelopes` class uses the `CollectsEnvelopes` trait | `eventLog.NeedsEnvelopes.CollectsEnvelopes.required` |
| `CollectsEnvelopesMustImplementNeedsEnvelopes` | class | Anything that uses `CollectsEnvelopes` implements `NeedsEnvelopes` | `eventLog.CollectsEnvelopes.NeedsEnvelopes.required` |
| `NeedsSentMustUseRecordsResult` | class | A `NeedsSent` class uses the `RecordsResult` trait | `eventLog.NeedsSent.RecordsResult.required` |
| `RecordsResultMustImplementNeedsSent` | class | Anything that uses `RecordsResult` implements `NeedsSent` | `eventLog.RecordsResult.NeedsSent.required` |

## Reflection extension

`Extensions\DisablesSerializesModels` is a `MethodsClassReflectionExtension`, not a
rule. It teaches PHPStan about the `withoutSerializesModels()` macro. The
`Support\Events\Dispatcher\Mixins\DisablesSerializesModels` mixin adds that macro
to Laravel's `Dispatcher` and the `Event` facade. Without the extension, static
analysis would report the macro as an undefined method.

## Ignored errors

`rules.neon` also declares two `ignoreErrors`, both scoped to
`Concerns/SerializesModels.php` (`trait.unused` and `match.alwaysFalse`). They are
artifacts of wrapping Laravel's `SerializesModels`, and they are expected.
