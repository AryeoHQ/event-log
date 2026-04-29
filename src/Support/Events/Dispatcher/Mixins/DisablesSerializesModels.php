<?php

declare(strict_types=1);

namespace Support\Events\Dispatcher\Mixins;

use Closure;

/** @mixin \Illuminate\Events\Dispatcher */
final class DisablesSerializesModels
{
    /** @var bool|array<int, class-string> */
    public static bool|array $events = false;

    public function withoutSerializesModels(): Closure
    {
        return function (string|array|Closure $events, null|Closure $callback = null): mixed {
            throw_unless(
                $events instanceof Closure || $callback !== null,
                \InvalidArgumentException::class,
                'A callback is required when disabling by event class.',
            );

            [DisablesSerializesModels::$events, $callback] = match (true) {
                $events instanceof Closure => [true, $events],
                is_string($events) => [[$events], $callback],
                default => [$events, $callback],
            };

            try {
                return $callback();
            } finally {
                DisablesSerializesModels::$events = false;
            }
        };
    }
}
