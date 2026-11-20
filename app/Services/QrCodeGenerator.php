<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeGenerator
{
    public function pngBinario(string $contenido): string
    {
        $qrcode = new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => 8,
        ]));

        return $qrcode->render($contenido);
    }

    public function dataUri(string $contenido): string
    {
        $qrcode = new QRCode(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 6,
        ]));

        return $qrcode->render($contenido);
    }
}
