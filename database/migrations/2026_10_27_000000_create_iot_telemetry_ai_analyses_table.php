<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_telemetry_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_telemetry_measurement_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->string('reliability', 24)->nullable()->index();
            $table->boolean('approved_for_use')->nullable()->index();
            $table->string('risk_level', 24)->nullable()->index();
            $table->text('risk_summary')->nullable();
            $table->text('reliability_reason')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('disclaimer')->nullable();
            $table->string('model', 100)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_telemetry_ai_analyses');
    }
};
