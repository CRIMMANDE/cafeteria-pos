<?php

namespace App\Services\ThermalPrinter;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class WindowsUsbPrinterTransport
{
    public function send(string $payload, array $config): void
    {
        $printerName = trim((string) (
            $config['usb_printer_name']
            ?? $config['printer_name']
            ?? $config['share_name']
            ?? ''
        ));

        if ($printerName === '') {
            throw new RuntimeException('Falta el nombre de la impresora USB/Windows.');
        }

        $scriptPath = base_path('scripts/Send-RawPrinterJob.ps1');

        if (!File::exists($scriptPath)) {
            throw new RuntimeException('No existe el script de impresion RAW para Windows.');
        }

        $jobDirectory = storage_path('app/thermal-printer');
        File::ensureDirectoryExists($jobDirectory);

        $jobFile = $jobDirectory . DIRECTORY_SEPARATOR . 'ticket_' . uniqid('', true) . '.bin';
        File::put($jobFile, $payload);

        $result = Process::timeout(max(5, (int) ($config['timeout_seconds'] ?? 3) + 2))
            ->run([
                'powershell',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                $scriptPath,
                '-PrinterName',
                $printerName,
                '-FilePath',
                $jobFile,
            ]);

        File::delete($jobFile);

        if ($result->failed()) {
            throw new RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'No se pudo enviar el ticket a la impresora USB.'));
        }
    }
}
