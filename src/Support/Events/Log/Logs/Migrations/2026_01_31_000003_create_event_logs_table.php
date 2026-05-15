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
            $table->uuid('idempotency_key')->unique()->index();
            $table->string('type')->index();
            $table->json('context');
            $table->json('data')->nullable();
            $table->binary('event');
            $table->nullableUuidMorphs('loggable');
            $table->timestampTz('occurred_at')->index();
            $table->timestampsTz();
        });
    }
};
