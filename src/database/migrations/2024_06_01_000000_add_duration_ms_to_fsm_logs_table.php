<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsm_logs', function (Blueprint $table) {
            $table->unsignedInteger('duration_ms')->nullable()->after('exception_details');
        });
    }

    public function down(): void
    {
        Schema::table('fsm_logs', function (Blueprint $table) {
            $table->dropColumn('duration_ms');
        });
    }
};
