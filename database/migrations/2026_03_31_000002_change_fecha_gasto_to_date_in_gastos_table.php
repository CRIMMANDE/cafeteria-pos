<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gastos') || !Schema::hasColumn('gastos', 'fecha_gasto')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE gastos MODIFY fecha_gasto DATE NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE gastos ALTER COLUMN fecha_gasto TYPE DATE USING fecha_gasto::date');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE gastos ALTER COLUMN fecha_gasto DATE NOT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('gastos') || !Schema::hasColumn('gastos', 'fecha_gasto')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE gastos MODIFY fecha_gasto DATETIME NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE gastos ALTER COLUMN fecha_gasto TYPE TIMESTAMP USING fecha_gasto::timestamp');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE gastos ALTER COLUMN fecha_gasto DATETIME2 NOT NULL');
        }
    }
};
