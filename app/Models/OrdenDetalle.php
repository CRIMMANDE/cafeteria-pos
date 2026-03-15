<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class OrdenDetalle extends Model
{
    protected $fillable = [
        'orden_id',
        'producto_id',
        'cantidad',
        'precio',
        'nota',
        'impreso'
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function extras()
    {
        return $this->hasMany(OrdenDetalleExtra::class);
    }

    public function opciones()
    {
        return $this->hasMany(OrdenDetalleOpcion::class);
    }

    public function subtotal()
    {
        return $this->precio * $this->cantidad;
    }
}
