<?php

namespace App\Services\SalesCut;

use App\Models\Orden;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SalesCutService
{
    public const FILTERS = [
        'bruma' => 'BRUMA',
        'brumita' => 'BRUMITA',
        'todas' => 'TODAS',
    ];

    private const BRANCH_CODES = ['bruma', 'brumita'];

    public function summary(CarbonInterface $inicio, CarbonInterface $fin, string $filter = 'todas'): array
    {
        $this->assertValidFilter($filter);

        $branches = [];
        foreach ($this->codesFor($filter) as $code) {
            $branches[$code] = $this->calculateForBranch($inicio, $fin, $code);
        }

        $totals = $this->aggregate($branches);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'filtro' => $filter,
            'sucursal_nombre' => self::FILTERS[$filter],
            'sucursales' => $branches,
            ...$totals,
        ];
    }

    public function exportRows(CarbonInterface $inicio, CarbonInterface $fin, string $filter = 'todas'): array
    {
        $this->assertValidFilter($filter);

        $table = (new Orden)->getTable();
        $orderColumns = Schema::getColumnListing($table);
        $qualifiedColumns = array_map(fn (string $column) => "{$table}.{$column}", $orderColumns);

        $rows = $this->baseQuery($inicio, $fin, $filter)
            ->select([...$qualifiedColumns, 'sucursales.nombre as sucursal_nombre'])
            ->orderBy("{$table}.created_at")
            ->orderBy("{$table}.id")
            ->get()
            ->map(function (Orden $order) use ($orderColumns) {
                $normalized = [];
                foreach ($orderColumns as $column) {
                    $normalized[] = $this->normalizeCellValue($order->getRawOriginal($column));
                }

                $normalized[] = $this->normalizeCellValue($order->getAttribute('sucursal_nombre'));

                return $normalized;
            })
            ->all();

        return [
            'columns' => [...$orderColumns, 'Sucursal'],
            'rows' => $rows,
        ];
    }

    public static function isValidFilter(string $filter): bool
    {
        return array_key_exists($filter, self::FILTERS);
    }

    private function calculateForBranch(CarbonInterface $inicio, CarbonInterface $fin, string $code): array
    {
        $orders = $this->baseQuery($inicio, $fin, $code)
            ->get(['ordens.id', 'ordens.total', 'ordens.metodo_pago']);

        $subtotalCents = 0;
        $cashCents = 0;
        $cardGrossCents = 0;

        foreach ($orders as $order) {
            $cents = Money::toCents($order->getRawOriginal('total'));

            if ($cents === null) {
                throw new RuntimeException("La orden {$order->getKey()} tiene un total monetario inválido.");
            }

            $subtotalCents += $cents;

            if ($order->metodo_pago === 'efectivo') {
                $cashCents += $cents;
            }

            if ($order->metodo_pago === 'tarjeta') {
                $cardGrossCents += $cents;
            }
        }

        $cardNetCents = $this->cardNetCents($cardGrossCents);

        return [
            'codigo' => $code,
            'sucursal_nombre' => self::FILTERS[$code],
            'ordenes_count' => $orders->count(),
            'subtotal' => $this->amount($subtotalCents),
            'parcial_efectivo' => $this->amount($cashCents),
            'parcial_tarjeta_bruto' => $this->amount($cardGrossCents),
            'parcial_tarjeta_neto' => $this->amount($cardNetCents),
            'total_final' => $this->amount($cashCents + $cardNetCents),
        ];
    }

    private function aggregate(array $branches): array
    {
        $metrics = [
            'ordenes_count' => 0,
            'subtotal' => 0,
            'parcial_efectivo' => 0,
            'parcial_tarjeta_bruto' => 0,
            'parcial_tarjeta_neto' => 0,
            'total_final' => 0,
        ];

        foreach ($branches as $branch) {
            $metrics['ordenes_count'] += (int) $branch['ordenes_count'];

            foreach (['subtotal', 'parcial_efectivo', 'parcial_tarjeta_bruto', 'parcial_tarjeta_neto', 'total_final'] as $key) {
                $cents = Money::toCents($branch[$key]);

                if ($cents === null) {
                    throw new RuntimeException("El resumen de {$branch['sucursal_nombre']} contiene un importe inválido.");
                }

                $metrics[$key] += $cents;
            }
        }

        foreach (['subtotal', 'parcial_efectivo', 'parcial_tarjeta_bruto', 'parcial_tarjeta_neto', 'total_final'] as $key) {
            $metrics[$key] = $this->amount($metrics[$key]);
        }

        return $metrics;
    }

    private function baseQuery(CarbonInterface $inicio, CarbonInterface $fin, string $filter): Builder
    {
        $query = Orden::query()
            ->join('sucursales', 'ordens.sucursal_id', '=', 'sucursales.id')
            ->where('ordens.estado', 'pagada')
            ->where('ordens.created_at', '>=', $inicio)
            ->where('ordens.created_at', '<=', $fin);

        if ($filter === 'todas') {
            return $query->whereIn('sucursales.codigo', self::BRANCH_CODES);
        }

        return $query->where('sucursales.codigo', $filter);
    }

    private function codesFor(string $filter): array
    {
        return $filter === 'todas' ? self::BRANCH_CODES : [$filter];
    }

    private function cardNetCents(int $grossCents): int
    {
        return intdiv(($grossCents * 95) + 50, 100);
    }

    private function amount(int $cents): float
    {
        return (float) Money::fromCents($cents);
    }

    private function assertValidFilter(string $filter): void
    {
        if (! self::isValidFilter($filter)) {
            throw new \InvalidArgumentException("Filtro de sucursal no válido: {$filter}.");
        }
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
