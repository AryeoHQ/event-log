<?php

declare(strict_types=1);

namespace Support\Events\Log\DeliveryAttempts\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_delivery_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_log_delivery_id')->index()->constrained('event_log_deliveries');
            $table->string('status');
            $table->text('response')->nullable();
            $table->timestampTz('attempted_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'updated_at']); // Watchdog stuck() sweep
        });
    }
};
