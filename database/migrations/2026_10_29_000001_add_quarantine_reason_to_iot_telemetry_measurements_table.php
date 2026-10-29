<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_telemetry_measurements', function (Blueprint $table) {
            $table->string('quarantine_reason', 32)->nullable()->after('received_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('iot_telemetry_measurements', function (Blueprint $table) {
            $table->dropColumn('quarantine_reason');
        });
    }
};
