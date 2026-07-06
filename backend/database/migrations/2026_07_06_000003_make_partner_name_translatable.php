<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE partners
                ALTER COLUMN name TYPE json
                USING CASE
                    WHEN left(trim(name::text), 1) = '{'
                        THEN name::json
                    ELSE json_build_object('ka', name, 'en', name, 'ru', name, 'ua', name)
                END
            ");

            return;
        }

        Schema::table('partners', function ($table) {
            $table->json('name_json')->nullable();
        });

        foreach (DB::table('partners')->get() as $row) {
            $plain = $row->name;
            $decoded = is_string($plain) ? json_decode($plain, true) : null;
            $json = is_array($decoded)
                ? $decoded
                : ['ka' => $plain, 'en' => $plain, 'ru' => $plain, 'ua' => $plain];

            DB::table('partners')->where('id', $row->id)->update(['name_json' => json_encode($json)]);
        }

        Schema::table('partners', function ($table) {
            $table->dropColumn('name');
        });

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE partners RENAME COLUMN name_json TO name');
        } else {
            Schema::table('partners', function ($table) {
                $table->renameColumn('name_json', 'name');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE partners ALTER COLUMN name TYPE varchar(255) USING COALESCE(name->>'ka', '')");

            return;
        }

        Schema::table('partners', function ($table) {
            $table->string('name_plain')->nullable();
        });

        foreach (DB::table('partners')->get() as $row) {
            $decoded = json_decode($row->name, true);
            $plain = is_array($decoded) ? ($decoded['ka'] ?? $decoded['en'] ?? '') : (string) $row->name;
            DB::table('partners')->where('id', $row->id)->update(['name_plain' => $plain]);
        }

        Schema::table('partners', function ($table) {
            $table->dropColumn('name');
        });

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE partners RENAME COLUMN name_plain TO name');
        } else {
            Schema::table('partners', function ($table) {
                $table->renameColumn('name_plain', 'name');
            });
        }
    }
};
