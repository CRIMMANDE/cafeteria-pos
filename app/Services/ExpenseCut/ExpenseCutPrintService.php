<?php

namespace App\Services\ExpenseCut;

use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;

class ExpenseCutPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
    ) {
    }

    public function print(array $summary): PrintResult
    {
        $config = config('impresoras.cocina', []);
        $payload = (new ExpenseCutTicketFormatter($config))->build($summary);

        return $this->printer->send($payload, $config, null);
    }
}
