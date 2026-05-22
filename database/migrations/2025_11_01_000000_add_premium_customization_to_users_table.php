<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name_color')->nullable()->after('is_admin');
            $table->string('display_alias')->nullable()->after('display_name_color');
            $table->string('profile_border_style')->nullable()->after('display_alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['display_name_color', 'display_alias', 'profile_border_style']);
        });
    }
};
