<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fsm_event_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('model');
            $table->string('column_name');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('transition_name')->nullable();
            $table->timestampTz('occurred_at');
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at');

            // Indexes for efficient querying
            // Note: model_type and model_id index is automatically created by uuidMorphs()
            $table->index(['occurred_at']);
            $table->index(['column_name']);
            $table->index(['from_state', 'to_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fsm_event_logs');
    }
};
