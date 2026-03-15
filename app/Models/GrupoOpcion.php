<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoOpcion extends Model
{
    protected $fillable = [
        'producto_id',
        'nombre',
        'obligatorio',
        'multiple',
        'orden',
        'solo_si_opcion_id',
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
