<?php

namespace App\Services\ThermalPrinter;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class RawEscPosPrinter
{
    public function __construct(
        private readonly NetworkPrinterTransport $networkTransport,
        private readonly WindowsUsbPrinterTransport $usbTransport,
    ) {
    }

    public function send(string $payload, array $config, ?string $fallbackUrl = null): PrintResult
    {
        $validationError = $this->validateConfiguration($config);

        if ($validationError !== null) {
            return new PrintResult(
                ok: true,
                printed: false,
                message: $validationError,
                fallbackUrl: $fallbackUrl,
                error: $validationError
            );
        }

        $errors = [];
        $transportAttempts = $this->resolveTransportPriority($config);

        if ($transportAttempts === []) {
            return new PrintResult(
                ok: true,
                printed: false,
                message: 'No hay impresora configurada para esta salida.',
                fallbackUrl: $fallbackUrl,
                error: 'Sin configuracion de impresion'
            );
        }

        foreach ($transportAttempts as $transport) {
            try {
                if ($transport === 'red') {
                    $this->networkTransport->send($payload, $config);
                }

                if ($transport === 'usb') {
                    $this->usbTransport->send($payload, $config);
                }

                return new PrintResult(
                    ok: true,
                    printed: true,
                    message: 'Impresion enviada correctamente.',
                    transport: $transport,
                    fallbackUrl: $fallbackUrl
                );
            } catch (RuntimeException $exception) {
                $errors[] = strtoupper($transport) . ': ' . $exception->getMessage();

                Log::error('ESC/POS print error', [
                    'transport' => $transport,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return new PrintResult(
            ok: true,
            printed: false,
            message: 'La impresion directa fallo. Revisa la configuracion de la impresora.',
            fallbackUrl: $fallbackUrl,
            error: implode(' | ', $errors)
        );
    }

    private function resolveTransportPriority(array $config): array
    {
        $transports = [];

        if ($this->hasNetworkConfiguration($config)) {
            $transports[] = 'red';
        }

        if ($this->hasUsbConfiguration($config)) {
            $transports[] = 'usb';
        }

        return array_values(array_unique($transports));
    }

    private function validateConfiguration(array $config): ?string
    {
        $connectionType = $config['connection_type'] ?? 'usb';
        $hasNetwork = $this->hasNetworkConfiguration($config);
        $hasUsb = $this->hasUsbConfiguration($config);

        if (!$hasNetwork && !$hasUsb) {
            return 'No hay impresora configurada. Define una IP de red o el nombre de una impresora USB/Windows.';
        }

        if ($connectionType === 'red' && !$hasNetwork) {
            return 'La impresora esta en modo red, pero falta la IP configurada.';
        }

        if ($connectionType === 'red' && (int) ($config['network_port'] ?? 0) <= 0) {
            return 'La impresora de red requiere un puerto TCP valido.';
        }

        if ($connectionType === 'usb' && !$hasUsb && !$hasNetwork) {
            return 'La impresora esta en modo USB, pero falta el nombre de la impresora instalada en Windows.';
        }

        if ($hasNetwork && (int) ($config['network_port'] ?? 0) <= 0) {
            return 'La configuracion de red requiere un puerto TCP valido.';
        }

        return null;
    }

    private function hasNetworkConfiguration(array $config): bool
    {
        return trim((string) ($config['network_ip'] ?? '')) !== '';
    }

    private function hasUsbConfiguration(array $config): bool
    {
        return trim((string) ($config['usb_printer_name'] ?? '')) !== '';
    }
}
