<?php

declare(strict_types=1);

namespace Support\Events\Log\Envelopes;

use Illuminate\Database\Eloquent\Model;
use Support\Events\Log\Logs\Data\Version\Contracts\Version;

final class Envelope
{
    public readonly null|Version $version;

    public readonly Model $recipient;

    private function __construct(Model $recipient, null|Version $version = null)
    {
        $this->version = $version;
        $this->recipient = $recipient;
    }

    public static function make(Model $recipient, null|Version $version = null): self
    {
        return new self($recipient, $version);
    }
}
