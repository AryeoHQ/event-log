<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('idempotency_key')->unique();
            $table->string('type');
            $table->string('status');
            $table->json('context');
            $table->json('data')->nullable();
            $table->binary('event');

            // Declared manually (over uuidMorphs) in favor of a wider composite manually defined below
            $table->string('loggable_type')->nullable();
            $table->uuid('loggable_id')->nullable();

            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['occurred_at', 'id']); // Handles most common occurred_at, id use case.
            $table->index(['type', 'occurred_at', 'id']); // Handles type-filtered use case
            $table->index(['loggable_type', 'loggable_id', 'occurred_at', 'id']); // Handles reverse relationship lookup use case
            $table->index(['status', 'updated_at']); // Watchdog stuck() sweep
        });
    }
};
