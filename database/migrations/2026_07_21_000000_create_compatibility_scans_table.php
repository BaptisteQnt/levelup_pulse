<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compatibility_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('status', 32)->default('created')->index();
            $table->longText('hardware_payload')->nullable();
            $table->longText('requirements_payload')->nullable();
            $table->longText('result_payload')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('researched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('upload_expires_at')->index();
            $table->timestamp('purge_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'game_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compatibility_scans');
    }
};
