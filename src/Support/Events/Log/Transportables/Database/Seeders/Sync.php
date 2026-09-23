<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Database\Seeders;

use Illuminate\Database\Seeder;
use Support\Events\Log\Transportables\Synchronize;

final class Sync extends Seeder
{
    public function run(): void
    {
        Synchronize::make()->now();
    }
}
