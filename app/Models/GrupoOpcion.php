<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoOpcion extends Model
{
    protected $table = 'grupos_opciones';

    protected $fillable = [
        'producto_id',
        'slug',
        'nombre',
        'tipo',
        'modalidad',
        'scope_modalidad',
        'obligatorio',
        'multiple',
        'orden',
        'orden_visual',
        'solo_si_opcion_id',
        'activo',
        'area_aplicacion',
        'es_grupo_salsa',
        'prioridad_visual',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'multiple' => 'boolean',
        'activo' => 'boolean',
        'es_grupo_salsa' => 'boolean',
        'orden' => 'integer',
        'orden_visual' => 'integer',
        'prioridad_visual' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function opciones()
    {
        return $this->hasMany(Opcion::class, 'grupo_opcion_id');
    }

    public function opcionPadre()
    {
        return $this->belongsTo(Opcion::class, 'solo_si_opcion_id');
    }
}
