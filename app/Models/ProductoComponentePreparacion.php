<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoComponentePreparacion extends Model
{
    protected $table = 'producto_componentes_preparacion';

    protected $fillable = [
        'producto_id',
        'modalidad',
        'area',
        'nombre_componente',
        'cantidad',
        'orden',
        'activo',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
