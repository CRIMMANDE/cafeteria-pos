<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LimpiarDatosPosCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'pos:limpiar-datos
        {--force : Ejecuta la limpieza sin confirmacion interactiva}';

    protected $description = 'Limpia datos transaccionales del POS sin tocar tablas de catalogo';

    public function handle(): int
    {
        if (! $this->confirmToProceed('Esta accion eliminara permanentemente los datos transaccionales del POS.')) {
            $this->warn('Operacion cancelada.');
            return self::FAILURE;
        }

        $tableNames = $this->resolveTransactionalTables();

        if ($tableNames === null) {
            return self::FAILURE;
        }

        $this->line('Limpiando datos...');

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tableNames as $table) {
                DB::table($table)->truncate();
                $this->line("? {$table} limpiado");
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('No se pudieron limpiar los datos: ' . $exception->getMessage());

            return self::FAILURE;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Datos limpiados correctamente.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>|null
     */
    private function resolveTransactionalTables(): ?array
    {
        $ordenesTable = $this->resolveOrdenesTable();

        if ($ordenesTable === null) {
            return null;
        }

        $tables = [
            'orden_detalle_componentes',
            'orden_detalle_extras',
            'orden_detalle_opciones',
            'orden_detalles',
            $ordenesTable,
        ];

        $missingTables = array_values(array_filter(
            $tables,
            static fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missingTables !== []) {
            $this->error('Faltan tablas transaccionales requeridas: ' . implode(', ', $missingTables));
            $this->line('No se realizo ningun cambio para evitar una limpieza parcial.');

            return null;
        }

        return $tables;
    }

    private function resolveOrdenesTable(): ?string
    {
        $available = array_values(array_filter(
            ['ordenes', 'ordens'],
            static fn (string $table): bool => Schema::hasTable($table)
        ));

        if (count($available) === 1) {
            return $available[0];
        }

        if ($available === []) {
            $this->error('No se encontro tabla de ordenes. Se esperaba una de estas: ordenes, ordens.');
            return null;
        }

        $this->error('Se encontraron multiples tablas de ordenes (' . implode(', ', $available) . ').');
        $this->line('Ajusta el comando para evitar ambiguedad antes de ejecutar una limpieza.');

        return null;
    }
}
