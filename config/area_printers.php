<?php

return [
    'cocina' => [
        'connection_type' => env('COCINA_PRINTER_CONNECTION', 'usb'),
        'usb_printer_name' => env('COCINA_PRINTER_USB_NAME', ''),
        'network_ip' => env('COCINA_PRINTER_IP', ''),
        'network_port' => (int) env('COCINA_PRINTER_PORT', 9100),
        'timeout_seconds' => (int) env('COCINA_PRINTER_TIMEOUT', 3),
        'characters_per_line' => (int) env('COCINA_PRINTER_CHARACTERS_PER_LINE', 48),
        'cut_at_end' => (bool) env('COCINA_PRINTER_CUT', true),
        'open_drawer' => false,
        'fallback_html_enabled' => true,
        'header' => env('COCINA_PRINTER_HEADER', 'COMANDA COCINA'),
    ],
    'barra' => [
        'connection_type' => env('BARRA_PRINTER_CONNECTION', 'usb'),
        'usb_printer_name' => env('BARRA_PRINTER_USB_NAME', ''),
        'network_ip' => env('BARRA_PRINTER_IP', ''),
        'network_port' => (int) env('BARRA_PRINTER_PORT', 9100),
        'timeout_seconds' => (int) env('BARRA_PRINTER_TIMEOUT', 3),
        'characters_per_line' => (int) env('BARRA_PRINTER_CHARACTERS_PER_LINE', 48),
        'cut_at_end' => (bool) env('BARRA_PRINTER_CUT', true),
        'open_drawer' => false,
        'fallback_html_enabled' => true,
        'header' => env('BARRA_PRINTER_HEADER', 'COMANDA BARRA'),
    ],
];
