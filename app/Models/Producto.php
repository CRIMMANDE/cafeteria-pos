<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'precio',
        'costo',
        'categoria_id'
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
