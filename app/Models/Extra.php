<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extra extends Model
{
    protected $fillable = [
        'nombre',
        'precio',
        'activo',
    ];

    public function ordenDetalleExtras()
    {
        return $this->hasMany(OrdenDetalleExtra::class);
    }
}
