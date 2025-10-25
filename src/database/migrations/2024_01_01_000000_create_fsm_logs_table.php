<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fsm_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('subject'); // Links to verb_events.id if a verb was involved
            $table->uuidMorphs('model');
            $table->string('fsm_column');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('transition_event')->nullable(); // User-defined event name that triggered transition
            $table->json('context_snapshot')->nullable();
            $table->text('exception_details')->nullable(); // Added for logging failure reasons
            $table->timestampTz('happened_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fsm_logs');
    }
};
