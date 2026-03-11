<?php

namespace App\Services\ThermalPrinter;

use RuntimeException;

class NetworkPrinterTransport
{
    public function send(string $payload, array $config): void
    {
        $ip = trim((string) ($config['network_ip'] ?? ''));
        $port = (int) ($config['network_port'] ?? 9100);
        $timeout = max(1, (int) ($config['timeout_seconds'] ?? 3));

        if ($ip === '') {
            throw new RuntimeException('Falta la IP de la impresora de red.');
        }

        if ($port <= 0) {
            throw new RuntimeException('El puerto TCP de la impresora no es valido.');
        }

        $socket = @fsockopen($ip, $port, $errorCode, $errorMessage, $timeout);

        if (!$socket) {
            throw new RuntimeException("No se pudo conectar a la impresora {$ip}:{$port}. {$errorMessage}", $errorCode);
        }

        stream_set_timeout($socket, $timeout);
        $written = fwrite($socket, $payload);
        $meta = stream_get_meta_data($socket);
        fclose($socket);

        if ($written === false || $written < strlen($payload)) {
            throw new RuntimeException('La impresora de red no acepto el ticket completo.');
        }

        if (!empty($meta['timed_out'])) {
            throw new RuntimeException('La conexion con la impresora excedio el tiempo de espera.');
        }
    }
}
