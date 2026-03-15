<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalleExtra extends Model
{
    protected $fillable = [
        'orden_detalle_id',
        'extra_id',
        'nombre_personalizado',
        'precio',
        'nota',
    ];

    public function ordenDetalle()
    {
        return $this->belongsTo(OrdenDetalle::class);
    }

    public function extra()
    {
        return $this->belongsTo(Extra::class);
    }
}
