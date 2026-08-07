<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use InvalidArgumentException;
use RuntimeException;

class PrinterDestinationResolver
{
    public function ticketFor(Orden $orden): string
    {
        $orden->loadMissing('sucursal');
        $sucursal = $orden->sucursal;

        if ($sucursal === null) {
            throw new RuntimeException("La orden {$orden->getKey()} no tiene una sucursal válida para impresión.");
        }

        $code = strtolower(trim((string) $sucursal->codigo));
        $destination = config("impresoras.ticket_destinations.{$code}");

        if (! is_string($destination) || ! $this->isConfiguredDestination($destination)) {
            throw new RuntimeException("No existe un destino de ticket válido para la sucursal {$code}.");
        }

        return $destination;
    }

    public function area(string $area): string
    {
        $destination = match (strtolower(trim($area))) {
            'cocina' => 'cocina',
            'barra' => 'barra',
            default => throw new InvalidArgumentException("Área de impresión no válida: {$area}."),
        };

        if (! $this->isConfiguredDestination($destination)) {
            throw new RuntimeException("El destino de impresión {$destination} no está configurado.");
        }

        return $destination;
    }

    public function salesCutFor(string $filter): string
    {
        $destination = match (strtolower(trim($filter))) {
            'bruma', 'todas' => 'cocina',
            'brumita' => 'barra',
            default => throw new InvalidArgumentException("Filtro de corte no válido: {$filter}."),
        };

        if (! $this->isConfiguredDestination($destination)) {
            throw new RuntimeException("El destino de corte {$destination} no está configurado.");
        }

        return $destination;
    }

    private function isConfiguredDestination(string $destination): bool
    {
        return in_array($destination, ['ventas', 'cocina', 'barra'], true)
            && is_array(config("impresoras.{$destination}"));
    }
}
