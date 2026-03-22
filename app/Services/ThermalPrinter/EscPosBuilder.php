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

    public function rasterImageFromPng(string $path, int $maxWidthDots = 380): self
    {
        if (!is_file($path) || !function_exists('imagecreatefrompng')) {
            return $this;
        }

        $image = @imagecreatefrompng($path);

        if (!$image) {
            return $this;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($image);

            return $this;
        }

        $maxWidthDots = max(32, $maxWidthDots);

        if ($sourceWidth > $maxWidthDots) {
            $targetWidth = $maxWidthDots;
            $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $white);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            imagedestroy($image);
            $image = $resized;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $bytesPerRow = (int) ceil($width / 8);

        $this->buffer .= "\x1D\x76\x30\x00";
        $this->buffer .= chr($bytesPerRow % 256) . chr(intdiv($bytesPerRow, 256));
        $this->buffer .= chr($height % 256) . chr(intdiv($height, 256));

        for ($y = 0; $y < $height; $y++) {
            for ($xByte = 0; $xByte < $bytesPerRow; $xByte++) {
                $byte = 0;

                for ($bit = 0; $bit < 8; $bit++) {
                    $x = ($xByte * 8) + $bit;

                    if ($x >= $width) {
                        continue;
                    }

                    $rgba = imagecolorat($image, $x, $y);
                    $alpha = ($rgba & 0x7F000000) >> 24;

                    if ($alpha >= 120) {
                        continue;
                    }

                    $red = ($rgba >> 16) & 0xFF;
                    $green = ($rgba >> 8) & 0xFF;
                    $blue = $rgba & 0xFF;
                    $gray = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);

                    if ($gray < 170) {
                        $byte |= (1 << (7 - $bit));
                    }
                }

                $this->buffer .= chr($byte);
            }
        }

        imagedestroy($image);
        $this->buffer .= "\n";

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
