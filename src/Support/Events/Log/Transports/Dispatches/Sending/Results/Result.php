<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Results;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final class Result implements Castable, JsonSerializable, Stringable
{
    public readonly null|int $code;

    public readonly string $message;

    private function __construct(string $message, null|int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public static function make(self|string|Stringable $message, null|int $code = null): self
    {
        return match (true) {
            $message instanceof self => $message,
            default => new self(
                (string) str($message)->limit(config('event_log.delivery_attempts.results.messages.length')),
                $code
            ),
        };
    }

    public function __toString(): string
    {
        return $this->code === null
            ? $this->message
            : "{$this->code}: {$this->message}";
    }

    /**
     * @return array{code: null|int, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }

    /**
     * @param  array<string>  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<self, self|string|\Stringable|null>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            /**
             * @param  array<string, mixed>  $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): null|Result
            {
                if ($value === null) {
                    return null;
                }

                /** @var array{code: null|int, message: string} $decoded */
                $decoded = json_decode((string) $value, associative: true, flags: JSON_THROW_ON_ERROR);

                return Result::make(
                    message: data_get($decoded, 'message', ''),
                    code: data_get($decoded, 'code'),
                );
            }

            /**
             * @param  array<string, mixed>  $attributes
             */
            public function set(Model $model, string $key, mixed $value, array $attributes): null|string
            {
                if ($value === null) {
                    return null;
                }

                // Write-once: keep the first value.
                if (($attributes[$key] ?? null) !== null) {
                    return $attributes[$key];
                }

                $result = match (true) {
                    $value instanceof Result => $value,
                    $value instanceof \Stringable, is_string($value) => Result::make($value),
                    default => throw new InvalidArgumentException(
                        Result::class.' cast expects '.Result::class.', string, or Stringable; got '.get_debug_type($value)
                    ),
                };

                return json_encode($result, JSON_THROW_ON_ERROR);
            }
        };
    }
}
