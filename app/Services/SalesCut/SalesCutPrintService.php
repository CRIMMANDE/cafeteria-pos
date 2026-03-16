<?php

namespace App\Services\SalesCut;

use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;

class SalesCutPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
    ) {
    }

    public function print(array $summary): PrintResult
    {
        $config = config('impresoras.cocina', []);
        $payload = (new SalesCutTicketFormatter($config))->build($summary);

        return $this->printer->send($payload, $config, null);
    }
}
