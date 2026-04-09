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
            $table->uuid('event_log_destination_id')->index();
            $table->jsonb('payload');
            $table->string('delivery_processor')->index();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_log_deliveries');
    }
};
