<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 64)->unique();
            $table->string('token_hash', 64)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('last_sequence')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('token_rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
