<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Date;
use Support\Events\Log\Deliveries\Status\Status;
use Support\Events\Log\Envelopes\Envelope;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\Support\Events\Log\Deliveries\Delivery>
 */
class Builder extends EloquentBuilder
{
    public function stuck(): self
    {
        return $this->where(function (self $query): void {
            $query->whereIn('status', [Status::Pending, Status::Locked]) // @phpstan-ignore staticMethod.dynamicCall
                ->where('updated_at', '<', Date::now()->toImmutable()->subMinutes((int) config('event_log.watchdog.grace')));
        });
    }

    /**
     * A delivery is identified by its Envelope, never by its raw columns. When an
     * Envelope is matched against — in any where* shape — expand it into the
     * columns it deconstructs to, wrapped as a single atomic group so the
     * caller's boolean (and / or / not) applies to the identity as a whole.
     * Closures pass straight through and recurse back here, so nested and
     * negated forms behave exactly as they would in a stock Laravel app.
     *
     * @param  (\Closure(static): mixed)|string|array<array-key, mixed>|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // String form: where('envelope', $envelope) or where('envelope', '!=', $envelope).
        // The 2-arg shorthand carries the Envelope in $operator; explicit forms in $value.
        if ($column === 'envelope') {
            [$envelope, $comparison] = $value instanceof Envelope
                ? [$value, $operator]
                : [$operator, '='];

            if ($envelope instanceof Envelope) {
                $this->whereEnvelope($envelope, $boolean, in_array($comparison, ['!=', '<>'], true));

                return $this;
            }
        }

        // Array form: where(['envelope' => $envelope, ...any other columns]).
        if (is_array($column) && ($column['envelope'] ?? null) instanceof Envelope) {
            $envelope = $column['envelope'];
            unset($column['envelope']);

            $this->whereEnvelope($envelope, $boolean, false, $column);

            return $this;
        }

        return parent::where($column, $operator, $value, $boolean); // @phpstan-ignore return.type
    }

    /**
     * Add an Envelope's identity as a single grouped where, honouring the caller's
     * boolean. An `!=` comparison negates the group; combined with a `not` boolean
     * the two cancel out, exactly as nested wheres behave in Laravel.
     *
     * @param  array<string, mixed>  $extra
     */
    private function whereEnvelope(Envelope $envelope, string $boolean, bool $operatorNegates, array $extra = []): void
    {
        $negated = str_contains($boolean, 'not') !== $operatorNegates;

        $identity = array_merge($extra, Delivery::identify($envelope));

        parent::where(
            fn (self $query) => $query->where($identity),
            null,
            null,
            (str_starts_with($boolean, 'or') ? 'or' : 'and').($negated ? ' not' : ''),
        );
    }
}
