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
     * NOT ACTUALLY USED, BUT NOT READY TO DELETE YET
     * 
     * Get a value representing the nine pixels surrounding a single spot
     * Not actually color brightness but more white is a lower value than more black
     */
    protected static function getSpotColorBrightness($imageResource, int $x, int $y): float {
        $valueList = [];
        foreach (range($x-1, $x+1) as $a) {
            foreach (range($y-1, $y+1) as $b) {
                $value = self::getColorBrightness($imageResource, $a, $b);
                if ($value === -1.00) {
                    continue;
                }
                $valueList[] = $value;
            }
        }

        return MathHelper::average($valueList);
    }

    /**
     * Get a value representing a pixel
     * Not actually color brightness but more white is a lower value than more black
     */
    protected static function getColorBrightness($imageResource, int $x, int $y): float {
        $rgb = @imagecolorat($imageResource, $x, $y);
        if($rgb === false) {
            return -1;
        }
        $colors = imagecolorsforindex($imageResource, $rgb);

        // RBG to Luma --- Digital ITU BT.601
        return 0.33 * $colors['red'] + 0.5 * $colors['green'] + 0.16 * $colors['blue'];
    }
}