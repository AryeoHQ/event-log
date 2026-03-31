<?php

declare(strict_types=1);

namespace Support\Events\Attempts\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_delivery_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('event_log_delivery_id')->index();
            $table->string('response')->nullable();
            $table->string('status');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_log_delivery_attempts');
    }
};
