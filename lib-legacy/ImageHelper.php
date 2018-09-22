<?php
class ImageHelper {
    // 1/2 brightness according to method here
    const GRAY = 384;

    // Not actually color brightness but more white is a lower value than more black
    public static function getColorBrightness($imageResource, int $x, int $y): int {
        $rgb = @imagecolorat($imageResource, $x, $y);
        if($rgb === false) {
            return -1;
        }
        $colors = imagecolorsforindex($imageResource, $rgb);
        $average = (256 * 3) - $colors['red'] - $colors['green'] - $colors['blue'];

        return $average;
    }
}