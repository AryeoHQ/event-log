<?php

declare(strict_types=1);

namespace Support\Events\Log\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Event;
use JsonSerializable;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Logs\Integrity\Corrupted;
use Support\Events\Log\Logs\Integrity\Tampered;

trait HasEvent
{
    private string $signingKey {
        get => $this->signingKey ??= config('app.key', '');
    }

    public function setEventAttribute(Recordable $event): void
    {
        $this->attributes['event'] = $this->prepareEvent($event);

        $data = $event->loggable->toLoggable();

        $this->forceFill([
            'type' => $this->event->alias,
            'loggable' => $this->event->loggable,
            'data' => match (true) {
                $data instanceof JsonSerializable => $data->jsonSerialize(),
                $data instanceof Jsonable => json_decode($data->toJson(), true),
                $data instanceof Arrayable => $data->toArray(),
                is_array($data) => $data,
                default => iterator_to_array($data),
            },
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

        if (! hash_equals($signature, hash_hmac('sha256', $serialized, $this->signingKey))) {
            return new Tampered($value);
        }

        return $serialized;
    }
}
