<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalleExtra extends Model
{
    protected $fillable = [
        'orden_detalle_id',
        'extra_id',
        'cantidad',
        'nombre_personalizado',
        'precio_unitario',
        'subtotal',
        'precio',
        'nota',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'float',
        'subtotal' => 'float',
        'precio' => 'float',
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
