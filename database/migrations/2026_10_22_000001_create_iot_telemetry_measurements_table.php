<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_telemetry_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained()->cascadeOnDelete();
            $table->decimal('temperature', 5, 2);
            $table->decimal('humidity', 5, 2);
            $table->unsignedBigInteger('sequence');
            $table->timestamp('measured_at')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamps();

            $table->unique(
                ['iot_device_id', 'sequence'],
                'iot_telemetry_device_sequence_unique',
            );
            $table->index(['iot_device_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_telemetry_measurements');
    }
};
