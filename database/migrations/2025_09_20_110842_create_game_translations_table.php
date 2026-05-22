<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('game_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->index();
            $table->string('lang', 5)->index();           // 'fr'
            $table->longText('summary')->nullable();
            $table->longText('storyline')->nullable();
            $table->string('provider')->nullable();       // 'DeeplTranslator'
            $table->string('source_hash', 64)->nullable();// sha256 du texte EN
            $table->timestamps();
            $table->unique(['game_id','lang']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('game_translations');
    }
};
