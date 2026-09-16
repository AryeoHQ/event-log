<?php

declare(strict_types=1);

namespace Support\Events\Log\Relays\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_relays', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_log_id')->index()->constrained('event_logs');
            $table->string('transport');
            $table->string('status');
            $table->timestampsTz();

            $table->index(['status', 'updated_at']); // Watchdog stuck() sweep
        });
    }
};
