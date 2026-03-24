<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenDetalle extends Model
{
    protected $fillable = [
        'orden_id',
        'producto_id',
        'es_otro_manual',
        'nombre_personalizado',
        'area_preparacion',
        'precio_manual',
        'cantidad',
        'modalidad',
        'precio_base',
        'incremento_modalidad',
        'precio',
        'nota',
        'impreso'
    ];

    protected $casts = [
        'es_otro_manual' => 'boolean',
        'precio_manual' => 'float',
        'precio_base' => 'float',
        'incremento_modalidad' => 'float',
        'precio' => 'float',
        'impreso' => 'boolean',
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

    public function componentes()
    {
        return $this->hasMany(OrdenDetalleComponente::class);
    }

    public function subtotal()
    {
        return $this->precio * $this->cantidad;
    }
}
