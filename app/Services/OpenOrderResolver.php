<?php

namespace App\Services;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Sucursal;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OpenOrderResolver
{
    /**
     * @param  array<int, string>  $with
     */
    public function forMesa(Sucursal $sucursal, Mesa $mesa, array $with = []): ?Orden
    {
        if ((int) $mesa->sucursal_id !== (int) $sucursal->getKey()) {
            throw new NotFoundHttpException('La mesa no pertenece a esta sucursal.');
        }

        $ordenes = Orden::query()
            ->forSucursal($sucursal)
            ->where('mesa_id', $mesa->getKey())
            ->where('estado', 'abierta')
            ->when($with !== [], fn ($query) => $query->with($with))
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($ordenes->count() > 1) {
            throw new ConflictHttpException('Existen varias órdenes abiertas para la misma mesa y sucursal.');
        }

        return $ordenes->first();
    }
}
