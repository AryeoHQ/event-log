<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;
use Support\Events\Log;
use Tests\Fixtures\Support\Amqp;
use Tests\Fixtures\Support\Mqtt;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase;

    protected $enablesPackageDiscoveries = true;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            Log\Providers\Provider::class,
            Mqtt\Providers\Provider::class,
            Amqp\Providers\Provider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('recordables', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->uuid('non_recordable_id');
            $table->timestamps();
        });

        Schema::create('recordable_after_commits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
        });

        Schema::create('relayables', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestamps();
        });

        Schema::create('non_recordables', function (Blueprint $table): void {
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
