<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $fillable = [
        'mesa_id',
        'tipo',
        'total',
        'estado',
        'desc_empleado',
        'metodo_pago',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
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
