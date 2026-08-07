<?php

namespace App\Services\SalesCut;

use App\Services\ThermalPrinter\PrinterDestinationResolver;
use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;

class SalesCutPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
        private readonly PrinterDestinationResolver $destinationResolver,
    ) {}

    public function print(array $summary, string $filter): PrintResult
    {
        $destination = $this->destinationFor($filter);
        $config = config("impresoras.{$destination}", []);
        $payload = (new SalesCutTicketFormatter($config))->build($summary);

        return $this->printer->send($payload, $config, null);
    }

    public function destinationFor(string $filter): string
    {
        return $this->destinationResolver->salesCutFor($filter);
    }
}
