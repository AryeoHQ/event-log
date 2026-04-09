<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;
use Support\Events\Log\Providers\Provider;
use Tests\Fixtures\Support\Entities\Articles\Provider as ArticlesProvider;

abstract class TestCase extends Testbench\TestCase
{
    protected $enablesPackageDiscoveries = true;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            Provider::class,
            ArticlesProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->artisan('migrate');

        Schema::create('articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }
}
