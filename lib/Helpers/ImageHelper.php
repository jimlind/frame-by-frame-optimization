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
     * Get a list of values representing a slice of a column
     * Not actually color brightness but more white is a lower value than more black
     */
    public static function gatherColorBrightnessListRange($imageResource, int $x, int $y, int $length): float {
        $brightnessList = [];
        $brightnessLineList = [];

        //for ($i = 0; $i <= $length; $i++) {
            //$brightnessList[$y + $i] = self::getColorBrightness($imageResource, $x, $y + $i);
            $a = [];
            foreach (range($x, $x+1100) as $b) {
                $a[] = self::getColorBrightness($imageResource, $b, $y);
            }
            return MathHelper::average($a);

            $brightnessLineList[$y] = MathHelper::average($a);
        //}

        print_r(['aaa', $length, $x, $brightnessList, $brightnessLineList]);
        return $brightnessList;
    }

    public static function getRowAverageBrightness($imageResource, int $xLeft, int $xRight, int $y): float {
        $brightnessList = [];
        foreach (range($xLeft, $xRight) as $x) {
            $brightnessList[] = self::getColorBrightness($imageResource, $x, $y);
        }

        return MathHelper::average($brightnessList);
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