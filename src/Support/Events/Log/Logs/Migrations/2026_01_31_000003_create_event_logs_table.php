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
            $table->string('type')->index();
            $table->string('entity_id')->nullable();
            $table->string('entity_type');
            $table->longText('event');
            $table->nullableUuidMorphs('actor');
            $table->nullableUuidMorphs('subject');
            $table->string('status');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
