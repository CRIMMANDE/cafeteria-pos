<?php

namespace App\Services\ExpenseCut;

use App\Models\Gasto;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExpenseCutService
{
    public function summary(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $gastos = Gasto::query()
            ->active()
            ->betweenFechaGasto($inicio, $fin)
            ->orderBy('fecha_gasto')
            ->get(['id', 'monto']);

        $total = (float) $gastos->sum(fn (Gasto $gasto) => (float) $gasto->monto);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'gastos_count' => $gastos->count(),
            'total_gastos' => round($total, 2),
        ];
    }

    public function exportRows(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $columns = [
            'id',
            'fecha_gasto',
            'descripcion',
            'monto',
            'status',
            'cancelado_at',
            'created_at',
            'updated_at',
        ];

        $rows = $this->baseQuery($inicio, $fin)
            ->select($columns)
            ->orderBy('fecha_gasto')
            ->orderBy('id')
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

    private function baseQuery(CarbonInterface $inicio, CarbonInterface $fin): Builder
    {
        return DB::table((new Gasto())->getTable())
            ->where('fecha_gasto', '>=', $inicio)
            ->where('fecha_gasto', '<=', $fin);
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
