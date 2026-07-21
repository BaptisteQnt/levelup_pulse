<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $encryptedUserColumns = [
        'name',
        'phone',
        'address',
        'city',
        'cp',
        'country',
        'age',
        'display_name_color',
        'display_alias',
        'profile_border_style',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('phone')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('city')->nullable()->change();
            $table->text('cp')->nullable()->change();
            $table->text('country')->nullable()->change();
            $table->text('age')->change();
            $table->text('display_name_color')->nullable()->change();
            $table->text('display_alias')->nullable()->change();
            $table->text('profile_border_style')->nullable()->change();
        });

        $this->encryptTableColumns('users', $this->encryptedUserColumns);
        $this->encryptTableColumns('data_erasure_requests', ['details', 'admin_notes']);
        $this->encryptTableColumns('oauth_accounts', ['access_token', 'refresh_token']);
    }

    public function down(): void
    {
        $this->decryptTableColumns('oauth_accounts', ['access_token', 'refresh_token']);
        $this->decryptTableColumns('data_erasure_requests', ['details', 'admin_notes']);
        $this->decryptTableColumns('users', $this->encryptedUserColumns);

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('phone')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('cp')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->integer('age')->change();
            $table->string('display_name_color')->nullable()->change();
            $table->string('display_alias')->nullable()->change();
            $table->string('profile_border_style')->nullable()->change();
        });
    }

    private function encryptTableColumns(string $table, array $columns): void
    {
        DB::table($table)
            ->select(['id', ...$columns])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        if ($row->{$column} !== null) {
                            $updates[$column] = Crypt::encryptString((string) $row->{$column});
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }

    private function decryptTableColumns(string $table, array $columns): void
    {
        DB::table($table)
            ->select(['id', ...$columns])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        if ($row->{$column} !== null) {
                            $updates[$column] = Crypt::decryptString($row->{$column});
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }
};
