<?php

namespace App\Services\ThermalPrinter;

class PrintResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly bool $printed,
        public readonly string $message,
        public readonly ?string $transport = null,
        public readonly ?string $fallbackUrl = null,
        public readonly ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'printed' => $this->printed,
            'message' => $this->message,
            'transport' => $this->transport,
            'fallback_url' => $this->fallbackUrl,
            'error' => $this->error,
        ];
    }
}
