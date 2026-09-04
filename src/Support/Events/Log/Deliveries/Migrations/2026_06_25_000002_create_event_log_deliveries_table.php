<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_log_relay_id')->constrained('event_log_relays');

            // Declared manually (over uuidMorphs) in favor of wider composites manually defined below
            $table->string('recipient_type');
            $table->uuid('recipient_id');

            $table->string('version')->nullable();
            $table->unsignedSmallInteger('tries');
            $table->string('status');
            $table->timestampsTz();

            $table->index(['event_log_relay_id', 'recipient_type', 'recipient_id', 'version']); // firstOrCreate identity; leading column covers plain FK lookups
            $table->index(['recipient_type', 'recipient_id', 'status', 'id']); // recipient-scoped lookups, optionally filtered by status, with cursor pagination
            $table->index(['status', 'updated_at']); // Watchdog stuck() sweep
        });
    }
};
