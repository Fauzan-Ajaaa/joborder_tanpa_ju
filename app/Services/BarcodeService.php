<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    /**
     * Generate barcode image as SVG (tidak butuh GD/Imagick)
     */
    public static function generateBarcode(string $code, int $width = null, int $height = 60): string
    {
        try {
            if (empty($code)) {
                $code = 'PROD0000000000';
            }

            $generator = new BarcodeGeneratorSVG();
            $svg = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, $height);

            return 'data:image/svg+xml;base64,' . base64_encode($svg);

        } catch (\Exception $e) {
            \Log::error('Barcode generation failed: ' . $e->getMessage());
            return '';
        }
    }
}
