<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class OgImageController extends Controller
{
    public function default(): Response
    {
        $width = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);

        // Background: #0a0a0a
        $bg = imagecolorallocate($img, 10, 10, 10);
        imagefill($img, 0, 0, $bg);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $teal = imagecolorallocate($img, 45, 212, 191);
        $gray = imagecolorallocate($img, 120, 120, 120);

        // Draw the glasses logo centered
        $logoWidth = 280;
        $logoHeight = 130;
        $logoX = ($width - $logoWidth) / 2;
        $logoY = 160;

        $eyeWidth = (int)($logoWidth * 0.45);
        $eyeHeight = $logoHeight;
        $borderWidth = 10;
        $cornerRadius = (int)($eyeHeight * 0.075);

        // Left lens (rounded rect outline)
        $this->drawRoundedRect($img, (int)$logoX, (int)$logoY, (int)$logoX + $eyeWidth, (int)$logoY + $eyeHeight, $cornerRadius, $teal, $borderWidth);
        // Fill inside white
        $this->drawFilledRoundedRect($img, (int)$logoX + $borderWidth, (int)$logoY + $borderWidth, (int)$logoX + $eyeWidth - $borderWidth, (int)$logoY + $eyeHeight - $borderWidth, $cornerRadius, $white);

        // Left pupil — looking up-right
        $pupilSize = 22;
        $pupilOffsetX = 12;
        $pupilOffsetY = -14;
        $leftCx = (int)$logoX + (int)($eyeWidth / 2) + $pupilOffsetX;
        $leftCy = (int)$logoY + (int)($eyeHeight / 2) + $pupilOffsetY;
        imagefilledellipse($img, $leftCx, $leftCy, $pupilSize, $pupilSize, imagecolorallocate($img, 0, 0, 0));

        // Bridge
        $bridgeX = (int)$logoX + $eyeWidth;
        $bridgeY = (int)$logoY + (int)($eyeHeight * 0.55);
        $bridgeW = (int)($logoWidth * 0.1);
        $bridgeH = (int)($logoHeight * 0.16);
        imagefilledrectangle($img, $bridgeX, $bridgeY, $bridgeX + $bridgeW, $bridgeY + $bridgeH, $teal);

        // Right lens
        $rightX = (int)$logoX + $logoWidth - $eyeWidth;
        $this->drawRoundedRect($img, $rightX, (int)$logoY, $rightX + $eyeWidth, (int)$logoY + $eyeHeight, $cornerRadius, $teal, $borderWidth);
        $this->drawFilledRoundedRect($img, $rightX + $borderWidth, (int)$logoY + $borderWidth, $rightX + $eyeWidth - $borderWidth, (int)$logoY + $eyeHeight - $borderWidth, $cornerRadius, $white);

        // Right pupil — looking up-right
        $rightCx = $rightX + (int)($eyeWidth / 2) + $pupilOffsetX;
        $rightCy = (int)$logoY + (int)($eyeHeight / 2) + $pupilOffsetY;
        imagefilledellipse($img, $rightCx, $rightCy, $pupilSize, $pupilSize, imagecolorallocate($img, 0, 0, 0));

        // App name text below logo
        $fontSize = 64;
        $fontPath = $this->getFontPath();

        if ($fontPath) {
            $text = 'Better Doodle';
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textX = ($width - $textWidth) / 2;
            $textY = $logoY + $logoHeight + 90;
            imagettftext($img, $fontSize, 0, (int)$textX, (int)$textY, $teal, $fontPath, $text);

            // Subtitle
            $subSize = 28;
            $subText = 'Simple forms for everyone';
            $bbox2 = imagettfbbox($subSize, 0, $fontPath, $subText);
            $subWidth = $bbox2[2] - $bbox2[0];
            $subX = ($width - $subWidth) / 2;
            $subY = $textY + 60;
            imagettftext($img, $subSize, 0, (int)$subX, (int)$subY, $gray, $fontPath, $subText);
        } else {
            // Fallback without custom font
            $text = 'Better Doodle';
            $charWidth = imagefontwidth(5);
            $textWidth = strlen($text) * $charWidth;
            $textX = ($width - $textWidth) / 2;
            $textY = $logoY + $logoHeight + 40;
            imagestring($img, 5, (int)$textX, (int)$textY, $text, $teal);
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function getFontPath(): ?string
    {
        // Try to find Inter font — bundled via npm
        $paths = [
            '/usr/share/fonts/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function drawRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, $color, int $thickness): void
    {
        for ($i = 0; $i < $thickness; $i++) {
            // Top
            imageline($img, $x1 + $radius, $y1 + $i, $x2 - $radius, $y1 + $i, $color);
            // Bottom
            imageline($img, $x1 + $radius, $y2 - $i, $x2 - $radius, $y2 - $i, $color);
            // Left
            imageline($img, $x1 + $i, $y1 + $radius, $x1 + $i, $y2 - $radius, $color);
            // Right
            imageline($img, $x2 - $i, $y1 + $radius, $x2 - $i, $y2 - $radius, $color);
        }
        // Corners
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
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
