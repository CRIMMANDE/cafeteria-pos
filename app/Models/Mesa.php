<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class Mesa extends Model
{
    /** @deprecated Compatibilidad BRUMA; resolver por sucursal y tipo en la etapa 3. */
    public const EMPLOYEE_ID = 9998;

    /** @deprecated Compatibilidad BRUMA; resolver por sucursal y tipo en la etapa 3. */
    public const TAKEAWAY_ID = 9999;

    protected $fillable = [
        'id',
        'sucursal_id',
        'numero',
        'tipo',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /** @deprecated Usar ensureTakeawayForSucursal(). */
    public static function ensureTakeawayMesa(): self
    {
        if (Schema::hasTable('sucursales') && Schema::hasColumn('mesas', 'sucursal_id')) {
            return static::ensureLegacySpecial(static::TAKEAWAY_ID, 'llevar');
        }

        return static::query()->firstOrCreate(
            ['id' => static::TAKEAWAY_ID],
            [
                'numero' => static::TAKEAWAY_ID,
                'tipo' => 'llevar',
            ]
        );
    }

    /** @deprecated Usar ensureEmployeeForSucursal(). */
    public static function ensureEmployeeMesa(): self
    {
        if (Schema::hasTable('sucursales') && Schema::hasColumn('mesas', 'sucursal_id')) {
            return static::ensureLegacySpecial(static::EMPLOYEE_ID, 'empleados');
        }

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

    public function scopeForSucursal(Builder $query, Sucursal|int $sucursal): Builder
    {
        return $query->where('sucursal_id', $sucursal instanceof Sucursal ? $sucursal->getKey() : $sucursal);
    }

    public static function specialForSucursal(Sucursal|int $sucursal, string $tipo): ?self
    {
        static::validateSpecialType($tipo);

        return static::query()
            ->forSucursal($sucursal)
            ->whereNull('numero')
            ->where('tipo', $tipo)
            ->first();
    }

    public static function ensureSpecialForSucursal(Sucursal|int $sucursal, string $tipo): self
    {
        static::validateSpecialType($tipo);
        $sucursalId = $sucursal instanceof Sucursal ? $sucursal->getKey() : $sucursal;

        return static::query()->firstOrCreate(
            ['sucursal_id' => $sucursalId, 'numero' => null, 'tipo' => $tipo]
        );
    }

    public static function ensureTakeawayForSucursal(Sucursal|int $sucursal): self
    {
        return static::ensureSpecialForSucursal($sucursal, 'llevar');
    }

    public static function ensureEmployeeForSucursal(Sucursal|int $sucursal): self
    {
        return static::ensureSpecialForSucursal($sucursal, 'empleados');
    }

    private static function ensureLegacySpecial(int $id, string $tipo): self
    {
        $bruma = Sucursal::query()->where('codigo', 'bruma')->firstOrFail();
        $mesa = static::query()->find($id);

        if ($mesa === null) {
            $mesa = new static;
            $mesa->id = $id;
        }

        $mesa->fill(['sucursal_id' => $bruma->id, 'numero' => null, 'tipo' => $tipo]);
        $mesa->save();

        return $mesa;
    }

    private static function validateSpecialType(string $tipo): void
    {
        if (! in_array($tipo, ['llevar', 'empleados'], true)) {
            throw new InvalidArgumentException("El tipo {$tipo} no es una mesa especial válida.");
        }
    }
}
