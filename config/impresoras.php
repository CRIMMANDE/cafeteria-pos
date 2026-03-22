<?php

return [
    'cocina' => [
        'connection_type' => env('IMPRESORA_COCINA_DRIVER', env('COCINA_PRINTER_CONNECTION', 'usb')),
        'usb_printer_name' => env(
            'IMPRESORA_COCINA_NOMBRE',
            env('IMPRESORA_COCINA_SHARE', env('COCINA_PRINTER_USB_NAME', ''))
        ),
        'printer_name' => env('IMPRESORA_COCINA_NOMBRE', env('COCINA_PRINTER_USB_NAME', '')),
        'share_name' => env('IMPRESORA_COCINA_SHARE', ''),
        'network_ip' => env('IMPRESORA_COCINA_IP', env('COCINA_PRINTER_IP', '')),
        'network_port' => (int) env('IMPRESORA_COCINA_PUERTO', env('COCINA_PRINTER_PORT', 9100)),
        'timeout_seconds' => (int) env('IMPRESORA_COCINA_TIMEOUT', env('COCINA_PRINTER_TIMEOUT', 3)),
        'characters_per_line' => (int) env('IMPRESORA_COCINA_CARACTERES', env('COCINA_PRINTER_CHARACTERS_PER_LINE', 48)),
        'cut_at_end' => (bool) env('IMPRESORA_COCINA_CORTE', env('COCINA_PRINTER_CUT', true)),
        'open_drawer' => (bool) env('IMPRESORA_COCINA_ABRIR_CAJA', false),
        'fallback_html_enabled' => (bool) env('IMPRESORA_COCINA_FALLBACK_HTML', true),
        'header' => env('IMPRESORA_COCINA_HEADER', env('COCINA_PRINTER_HEADER', 'COMANDA COCINA')),
    ],

    'barra' => [
        'connection_type' => env('IMPRESORA_BARRA_DRIVER', env('BARRA_PRINTER_CONNECTION', 'usb')),
        'usb_printer_name' => env(
            'IMPRESORA_BARRA_NOMBRE',
            env('IMPRESORA_BARRA_SHARE', env('BARRA_PRINTER_USB_NAME', ''))
        ),
        'printer_name' => env('IMPRESORA_BARRA_NOMBRE', env('BARRA_PRINTER_USB_NAME', '')),
        'share_name' => env('IMPRESORA_BARRA_SHARE', ''),
        'network_ip' => env('IMPRESORA_BARRA_IP', env('BARRA_PRINTER_IP', '')),
        'network_port' => (int) env('IMPRESORA_BARRA_PUERTO', env('BARRA_PRINTER_PORT', 9100)),
        'timeout_seconds' => (int) env('IMPRESORA_BARRA_TIMEOUT', env('BARRA_PRINTER_TIMEOUT', 3)),
        'characters_per_line' => (int) env('IMPRESORA_BARRA_CARACTERES', env('BARRA_PRINTER_CHARACTERS_PER_LINE', 48)),
        'cut_at_end' => (bool) env('IMPRESORA_BARRA_CORTE', env('BARRA_PRINTER_CUT', true)),
        'open_drawer' => (bool) env('IMPRESORA_BARRA_ABRIR_CAJA', false),
        'fallback_html_enabled' => (bool) env('IMPRESORA_BARRA_FALLBACK_HTML', true),
        'header' => env('IMPRESORA_BARRA_HEADER', env('BARRA_PRINTER_HEADER', 'COMANDA BARRA')),
    ],

    'ventas' => [
        'connection_type' => env('IMPRESORA_VENTAS_DRIVER', env('THERMAL_PRINTER_CONNECTION', 'usb')),
        'usb_printer_name' => env(
            'IMPRESORA_VENTAS_NOMBRE',
            env('IMPRESORA_VENTAS_SHARE', env('THERMAL_PRINTER_USB_NAME', ''))
        ),
        'printer_name' => env('IMPRESORA_VENTAS_NOMBRE', env('THERMAL_PRINTER_USB_NAME', '')),
        'share_name' => env('IMPRESORA_VENTAS_SHARE', ''),
        'network_ip' => env('IMPRESORA_VENTAS_IP', env('THERMAL_PRINTER_IP', '')),
        'network_port' => (int) env('IMPRESORA_VENTAS_PUERTO', env('THERMAL_PRINTER_PORT', 9100)),
        'timeout_seconds' => (int) env('IMPRESORA_VENTAS_TIMEOUT', env('THERMAL_PRINTER_TIMEOUT', 3)),
        'ticket_width_mm' => (int) env('IMPRESORA_VENTAS_ANCHO_MM', env('THERMAL_PRINTER_WIDTH_MM', 80)),
        'characters_per_line' => (int) env('IMPRESORA_VENTAS_CARACTERES', env('THERMAL_PRINTER_CHARACTERS_PER_LINE', 48)),
        'cut_at_end' => (bool) env('IMPRESORA_VENTAS_CORTE', env('THERMAL_PRINTER_CUT', true)),
        'open_drawer' => (bool) env('IMPRESORA_VENTAS_ABRIR_CAJA', env('THERMAL_PRINTER_OPEN_DRAWER', false)),
        'fallback_html_enabled' => (bool) env('IMPRESORA_VENTAS_FALLBACK_HTML', env('THERMAL_PRINTER_FALLBACK_HTML', true)),
        'store_name' => env('IMPRESORA_VENTAS_TIENDA_NOMBRE', env('THERMAL_STORE_NAME', 'BRUMA CAFE')),
        'store_address' => env('IMPRESORA_VENTAS_TIENDA_DIRECCION', env('THERMAL_STORE_ADDRESS', '')),
        'store_phone' => env('IMPRESORA_VENTAS_TIENDA_TELEFONO', env('THERMAL_STORE_PHONE', '')),
        'store_logo_path' => env('IMPRESORA_VENTAS_LOGO_PATH', env('THERMAL_STORE_LOGO_PATH', 'public/images/bruma.png')),
        'store_logo_max_width_dots' => (int) env('IMPRESORA_VENTAS_LOGO_MAX_WIDTH', env('THERMAL_STORE_LOGO_MAX_WIDTH', 380)),
        'default_cashier' => env('IMPRESORA_VENTAS_CAJERO_DEFAULT', env('THERMAL_DEFAULT_CASHIER', 'Sistema')),
    ],
];

