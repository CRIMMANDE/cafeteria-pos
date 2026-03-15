<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalleComponente extends Model
{
    protected $table = 'orden_detalle_componentes';

    protected $fillable = [
        'orden_detalle_id',
        'area',
        'descripcion',
        'cantidad',
        'impreso',
    ];

    public function ordenDetalle()
    {
        return $this->belongsTo(OrdenDetalle::class);
    }
}
