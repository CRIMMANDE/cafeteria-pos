<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;

class ThermalPrinterService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
    ) {
    }

    public function printOrder(Orden $orden): PrintResult
    {
        $config = config('impresoras.ventas', []);
        $fallbackUrl = !empty($config['fallback_html_enabled']) ? url('/orden/imprimir/' . $orden->mesa_id) : null;
        $payload = (new TicketFormatter($config))->buildOrderTicket(
            $orden->loadMissing(['detalles.producto', 'detalles.opciones', 'detalles.extras.extra', 'pagos'])
        );

        return $this->printer->send($payload, $config, $fallbackUrl);
    }
}
