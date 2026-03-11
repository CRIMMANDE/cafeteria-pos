<?php

return [
    'connection_type' => env('THERMAL_PRINTER_CONNECTION', 'usb'),
    'usb_printer_name' => env('THERMAL_PRINTER_USB_NAME', ''),
    'network_ip' => env('THERMAL_PRINTER_IP', ''),
    'network_port' => (int) env('THERMAL_PRINTER_PORT', 9100),
    'timeout_seconds' => (int) env('THERMAL_PRINTER_TIMEOUT', 3),
    'ticket_width_mm' => (int) env('THERMAL_PRINTER_WIDTH_MM', 80),
    'characters_per_line' => (int) env('THERMAL_PRINTER_CHARACTERS_PER_LINE', 48),
    'cut_at_end' => (bool) env('THERMAL_PRINTER_CUT', true),
    'open_drawer' => (bool) env('THERMAL_PRINTER_OPEN_DRAWER', false),
    'fallback_html_enabled' => (bool) env('THERMAL_PRINTER_FALLBACK_HTML', true),
    'store_name' => env('THERMAL_STORE_NAME', 'BRUMA CAFE'),
    'store_address' => env('THERMAL_STORE_ADDRESS', ''),
    'store_phone' => env('THERMAL_STORE_PHONE', ''),
    'default_cashier' => env('THERMAL_DEFAULT_CASHIER', 'Sistema'),
];
