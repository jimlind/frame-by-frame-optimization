<?php
namespace Helpers;

class ImageHelper {
    /**
     * Get a list of values representing a column
     * Not actually color brightness but more white is a lower value than more black
     */
    public static function gatherColorBrightnessList($imageResource, int $x): array {
        $height = imagesy($imageResource);
        $brightnessList = [];
        for ($y = 0; $y <= $height; $y++) {
            $brightnessList[$y] = self::getColorBrightness($imageResource, $x, $y);
        }

        return $brightnessList;
    }

    /**
     * Get a value representing a pixel
     * Not actually color brightness but more white is a lower value than more black
     */
    protected static function getColorBrightness($imageResource, int $x, int $y): int {
        $rgb = @imagecolorat($imageResource, $x, $y);
        if($rgb === false) {
            return -1;
        }
        $colors = imagecolorsforindex($imageResource, $rgb);
        $average = (256 * 3) - $colors['red'] - $colors['green'] - $colors['blue'];

        return $average;
    }
}