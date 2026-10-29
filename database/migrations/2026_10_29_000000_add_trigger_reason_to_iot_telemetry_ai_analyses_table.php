<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_telemetry_ai_analyses', function (Blueprint $table) {
            $table->string('trigger_reason', 24)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('iot_telemetry_ai_analyses', function (Blueprint $table) {
            $table->dropColumn('trigger_reason');
        });
    }
};
