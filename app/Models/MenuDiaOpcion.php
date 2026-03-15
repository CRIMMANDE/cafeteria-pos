<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MenuDiaOpcion extends Model
{
    protected $table = 'menu_dia_opciones';

    protected $fillable = [
        'tipo',
        'nombre',
        'activo',
        'fecha',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha' => 'date',
    ];

    public function scopeOfType(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeForDate(Builder $query, Carbon|string $fecha): Builder
    {
        return $query->whereDate('fecha', Carbon::parse($fecha)->toDateString());
    }

    public function scopeActiveForDate(Builder $query, Carbon|string $fecha): Builder
    {
        return $query->forDate($fecha)->where('activo', true);
    }
}
