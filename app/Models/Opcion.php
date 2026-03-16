<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opcion extends Model
{
    protected $table = 'opciones';

    protected $fillable = [
        'grupo_opcion_id',
        'nombre',
        'incremento_precio',
        'incremento_costo',
        'codigo_corto',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'incremento_precio' => 'float',
        'incremento_costo' => 'float',
    ];

    public function grupoOpcion()
    {
        return $this->belongsTo(GrupoOpcion::class, 'grupo_opcion_id');
    }

    public function ordenDetalleOpciones()
    {
        return $this->hasMany(OrdenDetalleOpcion::class);
    }
}
