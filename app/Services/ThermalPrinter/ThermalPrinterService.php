<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ThermalPrinterService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
        private readonly PrinterDestinationResolver $destinationResolver,
    ) {}

    public function printOrder(Orden $orden): PrintResult
    {
        $orden->loadMissing(['sucursal', 'mesa', 'detalles.producto', 'detalles.opciones', 'detalles.extras.extra', 'pagos']);
        $destination = $this->destinationResolver->ticketFor($orden);
        $destinationConfig = config("impresoras.{$destination}", []);
        $branding = Arr::only(config('impresoras.ventas', []), [
            'store_name',
            'store_address',
            'store_phone',
            'store_logo_path',
            'store_logo_max_width_dots',
            'default_cashier',
        ]);
        $config = array_replace($destinationConfig, $branding);
        $fallbackUrl = ! empty($config['fallback_html_enabled'])
            ? route('pos.orden.printable', ['sucursal' => $orden->sucursal, 'orden' => $orden])
            : null;
        $payload = (new TicketFormatter($config))->buildOrderTicket(
            $orden
        );

        $result = $this->printer->send($payload, $config, $fallbackUrl);

        if (! $result->printed) {
            Log::warning('Ticket printing was not completed', [
                'destination' => $destination,
                'order_id' => $orden->getKey(),
                'branch' => $orden->sucursal->codigo,
            ]);
        }

        return $result;
    }
}
