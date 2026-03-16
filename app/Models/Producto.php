<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'precio',
        'costo',
        'permite_solo',
        'permite_desayuno',
        'permite_comida',
        'incremento_desayuno',
        'incremento_comida',
        'es_comida_dia',
        'activo',
        'categoria_id'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'permite_solo' => 'boolean',
        'permite_desayuno' => 'boolean',
        'permite_comida' => 'boolean',
        'es_comida_dia' => 'boolean',
        'precio' => 'float',
        'costo' => 'float',
        'incremento_desayuno' => 'float',
        'incremento_comida' => 'float',
    ];

    public function gruposOpciones()
    {
        return $this->hasMany(GrupoOpcion::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
