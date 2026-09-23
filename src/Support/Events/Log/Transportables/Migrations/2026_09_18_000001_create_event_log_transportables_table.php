<?php

declare(strict_types=1);

namespace Support\Events\Log\Transportables\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_transportables', function (Blueprint $table): void {
            $table->string('alias')->primary();
            $table->string('class');
            $table->json('transports');
            $table->timestampsTz();
        });
    }
};
