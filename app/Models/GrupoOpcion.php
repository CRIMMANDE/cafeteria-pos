<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoOpcion extends Model
{
    protected $table = 'grupos_opciones';

    protected $fillable = [
        'producto_id',
        'nombre',
        'modalidad',
        'obligatorio',
        'multiple',
        'orden',
        'solo_si_opcion_id',
        'activo',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'multiple' => 'boolean',
        'activo' => 'boolean',
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
