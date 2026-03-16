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

    protected $casts = [
        'activo' => 'boolean',
        'precio' => 'float',
    ];

    public function ordenDetalleExtras()
    {
        return $this->hasMany(OrdenDetalleExtra::class);
    }
}
