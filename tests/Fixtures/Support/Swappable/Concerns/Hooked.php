<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Swappable\Concerns;

trait Hooked
{
    public function initializeHooked(): void
    {
        $this->attributes['hooked'] = 'hooked';
        $this->attributes['name'] = 'hooked';

        $this->mergeCasts(['hooked' => 'string', 'name' => 'boolean']);
    }
}
