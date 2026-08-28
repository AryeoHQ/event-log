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
            $table->foreignUuid('event_log_relay_id')->index()->constrained('event_log_relays');

            // Declared manually (over uuidMorphs) in favor of a wider composite manually defined below
            $table->string('recipient_type');
            $table->uuid('recipient_id');

            $table->string('version')->nullable();
            $table->unsignedSmallInteger('tries');
            $table->string('status');
            $table->timestampsTz();

            $table->index(['recipient_type', 'recipient_id', 'id']);
            $table->index(['status', 'updated_at']); // Watchdog stuck() sweep
        });
    }
};
