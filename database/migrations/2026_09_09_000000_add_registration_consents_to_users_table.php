<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('over_18_declared')->default(false)->after('age');
            $table->text('over_18_declaration_label')->nullable()->after('over_18_declared');
            $table->timestamp('over_18_declared_at')->nullable()->after('over_18_declaration_label');
            $table->boolean('rgpd_consent')->default(false)->after('over_18_declared_at');
            $table->text('rgpd_consent_label')->nullable()->after('rgpd_consent');
            $table->timestamp('rgpd_consented_at')->nullable()->after('rgpd_consent_label');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'over_18_declared',
                'over_18_declaration_label',
                'over_18_declared_at',
                'rgpd_consent',
                'rgpd_consent_label',
                'rgpd_consented_at',
            ]);
        });
    }
};
