<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    public const EMPLOYEE_ID = 9998;
    public const TAKEAWAY_ID = 9999;

    protected $fillable = [
        'id',
        'numero',
        'tipo',
    ];

    public static function ensureTakeawayMesa(): self
    {
        return static::query()->firstOrCreate(
            ['id' => static::TAKEAWAY_ID],
            [
                'numero' => static::TAKEAWAY_ID,
                'tipo' => 'llevar',
            ]
        );
    }

    public static function ensureEmployeeMesa(): self
    {
        return static::query()->firstOrCreate(
            ['id' => static::EMPLOYEE_ID],
            [
                'numero' => static::EMPLOYEE_ID,
                'tipo' => 'empleados',
            ]
        );
    }

    public static function isEmployee(?int $mesaId): bool
    {
        return $mesaId === static::EMPLOYEE_ID;
    }

    public static function isTakeaway(?int $mesaId): bool
    {
        return $mesaId === static::TAKEAWAY_ID;
    }
}
