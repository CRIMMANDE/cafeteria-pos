<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    public const STATUS_ACTIVO = 'activo';
    public const STATUS_CANCELADO = 'cancelado';

    protected $table = 'gastos';

    protected $fillable = [
        'fecha_gasto',
        'descripcion',
        'monto',
        'status',
        'cancelado_at',
    ];

    protected $casts = [
        'fecha_gasto' => 'date',
        'monto' => 'decimal:2',
        'cancelado_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVO);
    }

    public function scopeBetweenFechaGasto(Builder $query, CarbonInterface $inicio, CarbonInterface $fin): Builder
    {
        return $query
            ->where('fecha_gasto', '>=', $inicio->toDateString())
            ->where('fecha_gasto', '<=', $fin->toDateString());
    }
}
