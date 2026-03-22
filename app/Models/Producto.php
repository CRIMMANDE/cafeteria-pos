<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'sku',
        'nombre',
        'precio',
        'costo',
        'permite_solo',
        'permite_desayuno',
        'permite_comida',
        'incremento_desayuno',
        'incremento_comida',
        'es_comida_dia',
        'usa_menu_dia',
        'usa_extras',
        'usa_notas',
        'usa_salsa',
        'orden',
        'activo',
        'categoria_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'permite_solo' => 'boolean',
        'permite_desayuno' => 'boolean',
        'permite_comida' => 'boolean',
        'es_comida_dia' => 'boolean',
        'usa_menu_dia' => 'boolean',
        'usa_extras' => 'boolean',
        'usa_notas' => 'boolean',
        'usa_salsa' => 'boolean',
        'precio' => 'float',
        'costo' => 'float',
        'incremento_desayuno' => 'float',
        'incremento_comida' => 'float',
        'orden' => 'integer',
    ];

    public function gruposOpciones()
    {
        return $this->hasMany(GrupoOpcion::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function extras()
    {
        return $this->belongsToMany(Extra::class, 'producto_extra')
            ->withPivot(['obligatorio', 'orden_visual', 'activo'])
            ->withTimestamps();
    }

    public function componentesPreparacion()
    {
        return $this->hasMany(ProductoComponentePreparacion::class);
    }
}
