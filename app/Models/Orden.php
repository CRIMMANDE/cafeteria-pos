<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $fillable = [
        'mesa_id',
        'total',
        'estado',
        'desc_empleado',
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
