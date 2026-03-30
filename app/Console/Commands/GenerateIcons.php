<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateIcons extends Command
{
    protected $signature = 'icons:generate';
    protected $description = 'Generate PWA icons with the glasses logo';

    public function handle(): void
    {
        $dir = public_path('icons');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->generateIcon(192, $dir . '/icon-192.png', false);
        $this->generateIcon(512, $dir . '/icon-512.png', false);
        $this->generateIcon(512, $dir . '/icon-512-maskable.png', true);

        $this->info('Icons generated in public/icons/');
    }

    private function generateIcon(int $size, string $path, bool $maskable): void
    {
        $img = imagecreatetruecolor($size, $size);

        $bg = imagecolorallocate($img, 10, 10, 10);
        imagefill($img, 0, 0, $bg);

        $white = imagecolorallocate($img, 255, 255, 255);
        $teal = imagecolorallocate($img, 45, 212, 191);
        $black = imagecolorallocate($img, 0, 0, 0);

        // Maskable icons need content within the safe zone (center 80%)
        $padding = $maskable ? (int)($size * 0.2) : (int)($size * 0.12);
        $contentSize = $size - ($padding * 2);

        // Glasses dimensions
        $logoWidth = (int)($contentSize * 0.85);
        $logoHeight = (int)($logoWidth * 0.46);
        $logoX = ($size - $logoWidth) / 2;
        $logoY = ($size - $logoHeight) / 2 - ($maskable ? 0 : (int)($size * 0.04));

        $eyeWidth = (int)($logoWidth * 0.45);
        $eyeHeight = $logoHeight;
        $borderWidth = max(3, (int)($size * 0.018));
        $cornerRadius = (int)($eyeHeight * 0.08);

        // Left lens
        $this->drawFilledRoundedRect($img, (int)$logoX, (int)$logoY, (int)$logoX + $eyeWidth, (int)$logoY + $eyeHeight, $cornerRadius, $teal);
        $this->drawFilledRoundedRect($img, (int)$logoX + $borderWidth, (int)$logoY + $borderWidth, (int)$logoX + $eyeWidth - $borderWidth, (int)$logoY + $eyeHeight - $borderWidth, $cornerRadius, $white);

        // Left pupil — looking up-right
        $pupilSize = max(6, (int)($size * 0.045));
        $pupilOffsetX = (int)($size * 0.02);
        $pupilOffsetY = -(int)($size * 0.025);
        $leftCx = (int)$logoX + (int)($eyeWidth / 2) + $pupilOffsetX;
        $leftCy = (int)$logoY + (int)($eyeHeight / 2) + $pupilOffsetY;
        imagefilledellipse($img, $leftCx, $leftCy, $pupilSize, $pupilSize, $black);

        // Bridge
        $bridgeX = (int)$logoX + $eyeWidth;
        $bridgeY = (int)$logoY + (int)($eyeHeight * 0.52);
        $bridgeW = (int)($logoWidth * 0.1);
        $bridgeH = (int)($logoHeight * 0.16);
        imagefilledrectangle($img, $bridgeX, $bridgeY, $bridgeX + $bridgeW, $bridgeY + $bridgeH, $teal);

        // Right lens
        $rightX = (int)$logoX + $logoWidth - $eyeWidth;
        $this->drawFilledRoundedRect($img, $rightX, (int)$logoY, $rightX + $eyeWidth, (int)$logoY + $eyeHeight, $cornerRadius, $teal);
        $this->drawFilledRoundedRect($img, $rightX + $borderWidth, (int)$logoY + $borderWidth, $rightX + $eyeWidth - $borderWidth, (int)$logoY + $eyeHeight - $borderWidth, $cornerRadius, $white);

        // Right pupil
        $rightCx = $rightX + (int)($eyeWidth / 2) + $pupilOffsetX;
        $rightCy = (int)$logoY + (int)($eyeHeight / 2) + $pupilOffsetY;
        imagefilledellipse($img, $rightCx, $rightCy, $pupilSize, $pupilSize, $black);

        imagepng($img, $path);
        imagedestroy($img);
    }

    private function drawFilledRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
    {
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
