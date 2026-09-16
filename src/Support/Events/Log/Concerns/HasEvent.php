<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Logs\Integrity\Corrupted;
use Support\Events\Log\Logs\Integrity\Tampered;

trait HasEvent
{
    private string $signingKey {
        get => $this->signingKey ??= config('app.key') ?? throw new MissingAppKeyException;
    }

    /** @var list<string> */
    private array $previousSigningKeys {
        get => $this->previousSigningKeys ??= config('app.previous_keys', []);
    }

    /** @var \Illuminate\Support\Collection<int, string> */
    private Collection $signingKeys {
        get => $this->signingKeys ??= collect([$this->signingKey, ...$this->previousSigningKeys]);
    }

    public function setEventAttribute(Recordable $event): void
    {
        $this->attributes['event'] = $this->prepareEvent($event);

        $this->forceFill([
            'type' => $this->event->alias,
            'loggable' => $this->event->loggable,
            'data' => $event->loggable->toLoggable(),
        ]);
    }

    public function prepareEvent(Recordable $event): string
    {
        $cloned = tap(
            (clone $event),
            fn (Recordable $cloned) => $cloned->loggable->unsetRelations()->withoutAppends() // unsetRelations() required over withoutRelations()
        );

        $serialized = Event::withoutSerializesModels(fn (): string => serialize($cloned));

        return $this->encode($this->sign($serialized));
    }

    public function getEventAttribute(string $value): Recordable|Corrupted|Tampered
    {
        $decoded = $this->decode($value);

        if ($decoded instanceof Corrupted) {
            return $decoded;
        }

        $verified = $this->verify($decoded);

        if ($verified instanceof Tampered) {
            return $verified;
        }

        return unserialize($verified);
    }

    private function encode(string $signed): string
    {
        return base64_encode($signed);
    }

    private function decode(string $value): string|Corrupted
    {
        $decoded = base64_decode($value, true);

        return $decoded === false ? new Corrupted($value) : $decoded;
    }

    private function sign(string $serialized): string
    {
        return hash_hmac('sha256', $serialized, $this->signingKey).$serialized;
    }

    private function verify(string $value): string|Tampered
    {
        $signature = substr($value, 0, 64);
        $serialized = substr($value, 64);

        return match ($this->signingKeys->contains(fn (string $key) => hash_equals($signature, hash_hmac('sha256', $serialized, $key)))) {
            true => $serialized,
            false => new Tampered($value),
        };
    }
}
