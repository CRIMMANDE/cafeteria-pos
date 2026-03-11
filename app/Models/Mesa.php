<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
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

    public static function isTakeaway(?int $mesaId): bool
    {
        return $mesaId === static::TAKEAWAY_ID;
    }
}
