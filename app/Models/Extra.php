<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extra extends Model
{
    protected $fillable = [
        'slug',
        'nombre',
        'precio',
        'activo',
        'permite_cantidad',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio' => 'float',
        'permite_cantidad' => 'boolean',
        'orden' => 'integer',
    ];

    public function ordenDetalleExtras()
    {
        return $this->hasMany(OrdenDetalleExtra::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_extra')
            ->withPivot(['obligatorio', 'orden_visual', 'activo'])
            ->withTimestamps();
    }
}
