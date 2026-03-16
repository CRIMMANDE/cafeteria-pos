<?php

namespace App\Services\SalesCut;

use App\Models\Orden;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesCutService
{
    public function summary(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $orders = Orden::query()
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<=', $fin)
            ->get(['id', 'total', 'metodo_pago']);

        $subtotal = (float) $orders->sum(fn (Orden $orden) => (float) $orden->total);
        $parcialEfectivo = (float) $orders
            ->where('metodo_pago', 'efectivo')
            ->sum(fn (Orden $orden) => (float) $orden->total);
        $parcialTarjetaBruto = (float) $orders
            ->where('metodo_pago', 'tarjeta')
            ->sum(fn (Orden $orden) => (float) $orden->total);
        $parcialTarjetaNeto = round($parcialTarjetaBruto * 0.95, 2);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'subtotal' => round($subtotal, 2),
            'parcial_efectivo' => round($parcialEfectivo, 2),
            'parcial_tarjeta_bruto' => round($parcialTarjetaBruto, 2),
            'parcial_tarjeta_neto' => $parcialTarjetaNeto,
            'total_final' => round($parcialEfectivo + $parcialTarjetaNeto, 2),
            'ordenes_count' => $orders->count(),
        ];
    }

    public function exportRows(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $table = (new Orden())->getTable();
        $columns = Schema::getColumnListing($table);

        $rows = $this->baseQuery($table, $inicio, $fin)
            ->select($columns)
            ->orderBy('created_at')
            ->get()
            ->map(function (object $row) use ($columns) {
                $normalized = [];
                foreach ($columns as $column) {
                    $normalized[] = $this->normalizeCellValue($row->{$column} ?? null);
                }

                return $normalized;
            })
            ->all();

        return [
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function baseQuery(string $table, CarbonInterface $inicio, CarbonInterface $fin): Builder
    {
        return DB::table($table)
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<=', $fin);
    }

    private function normalizeCellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }
}

