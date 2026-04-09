<?php

declare(strict_types=1);

namespace Support\Events\Log;

class Manager
{
    /** @var array<class-string, class-string> */
    protected array $providers = [];

    /**
     * @param  class-string  $destinationable
     * @param  class-string  $provider
     */
    public function register(string $destinationable, string $provider): void
    {
        $this->providers[$destinationable] = $provider;
    }

    /**
     * @param  class-string  $destinationable
     * @return class-string|null
     */
    public function getProvider(string $destinationable): null|string
    {
        return $this->providers[$destinationable] ?? null;
    }
}
