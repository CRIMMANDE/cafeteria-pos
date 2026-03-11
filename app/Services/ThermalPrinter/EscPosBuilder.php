<?php

namespace App\Services\ThermalPrinter;

class EscPosBuilder
{
    private string $buffer = '';

    public function initialize(): self
    {
        $this->buffer .= "\x1B\x40";

        return $this;
    }

    public function alignLeft(): self
    {
        $this->buffer .= "\x1B\x61\x00";

        return $this;
    }

    public function alignCenter(): self
    {
        $this->buffer .= "\x1B\x61\x01";

        return $this;
    }

    public function alignRight(): self
    {
        $this->buffer .= "\x1B\x61\x02";

        return $this;
    }

    public function bold(bool $enabled = true): self
    {
        $this->buffer .= $enabled ? "\x1B\x45\x01" : "\x1B\x45\x00";

        return $this;
    }

    public function doubleSize(bool $enabled = true): self
    {
        $this->buffer .= $enabled ? "\x1D\x21\x11" : "\x1D\x21\x00";

        return $this;
    }

    public function text(string $text): self
    {
        $this->buffer .= $text;

        return $this;
    }

    public function line(string $text = ''): self
    {
        $this->buffer .= $text . "\n";

        return $this;
    }

    public function feed(int $lines = 1): self
    {
        $this->buffer .= str_repeat("\n", max(0, $lines));

        return $this;
    }

    public function cut(): self
    {
        $this->buffer .= "\x1D\x56\x41\x03";

        return $this;
    }

    public function openDrawer(): self
    {
        $this->buffer .= "\x1B\x70\x00\x19\xFA";

        return $this;
    }

    public function bytes(): string
    {
        return $this->buffer;
    }
}
