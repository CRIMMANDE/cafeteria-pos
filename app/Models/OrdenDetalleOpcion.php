<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalleOpcion extends Model
{
    protected $table = 'orden_detalle_opciones';

    protected $fillable = [
        'orden_detalle_id',
        'opcion_id',
        'nombre',
        'incremento_precio',
        'incremento_costo',
    ];

    public function ordenDetalle()
    {
        return $this->belongsTo(OrdenDetalle::class);
    }

    public function opcion()
    {
        return $this->belongsTo(Opcion::class);
    }
}
