<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->decimal('monto_recibido', 10, 2)->nullable()->after('monto');
            $table->decimal('cambio', 10, 2)->nullable()->after('monto_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['monto_recibido', 'cambio']);
        });
    }
};
