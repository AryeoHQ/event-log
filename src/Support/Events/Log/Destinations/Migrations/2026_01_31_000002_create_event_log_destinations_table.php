<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_destinations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('event_log_id');
            $table->string('destination_processor')->index();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_log_destinations');
    }
};
