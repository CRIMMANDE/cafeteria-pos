<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Orden extends Model
{
    protected $fillable = [
        'sucursal_id',
        'mesa_id',
        'tipo',
        'referencia',
        'total',
        'estado',
        'desc_empleado',
        'metodo_pago',
    ];

    protected static function booted(): void
    {
        // Compatibilidad temporal hasta que la etapa 3 envíe la sucursal desde el flujo operativo.
        static::creating(function (Orden $orden): void {
            if ($orden->sucursal_id === null && Schema::hasTable('sucursales') && Schema::hasColumn('ordens', 'sucursal_id')) {
                $orden->sucursal_id = Sucursal::query()->where('codigo', 'bruma')->value('id');
            }
        });
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function scopeForSucursal(Builder $query, Sucursal|int $sucursal): Builder
    {
        return $query->where('sucursal_id', $sucursal instanceof Sucursal ? $sucursal->getKey() : $sucursal);
    }

    public function detalles()
    {
        return $this->hasMany(OrdenDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function calcularTotal()
    {
        return $this->detalles->sum(function ($detalle) {
            return $detalle->precio * $detalle->cantidad;
        });
    }
}
