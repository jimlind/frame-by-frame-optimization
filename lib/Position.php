<?php
class Position {
    protected static $blackThreshold = 700;
    protected static $whiteThreshold = 50;
    protected static $sproketXValue = 400;

    public static function getY(string $imageFile): int {
        $imageResource = imagecreatefromjpeg($imageFile);
        $borderBottom = self::findBlackBorderBottom($imageResource);
        if ($borderBottom > 100 && $borderBottom < 900) {
            // print_r('Using border bottom.' . PHP_EOL);
            return $borderBottom - 24;
        }

        $sproketMiddle = self::findSproketMiddle($imageResource);
        if ($sproketMiddle) {
            // print_r('Using sprocket middle.' . PHP_EOL);
            return $sproketMiddle;
        }

        // print_r('Nothing good detected.' . PHP_EOL);
        return 0;
    }

    protected static function findBlackBorderBottom($imageResource): int {
        $width = imagesx($imageResource) - 1;
        $yPosition = imagesy($imageResource) - 1;

        $failure = false;
        while (!$failure) {
            $colorValue = self::getAverageColor($imageResource, $width, $yPosition, $failure);
            if ($colorValue > self::$blackThreshold) {
                return $yPosition;
            }
            $yPosition--;
        }

        return 0;
    }

    protected static function findSproketMiddle($imageResource): int {
        // Starting at y=0, move down 1px at a time until you hit a dark enough value
        $y = 0;
        $failure = false;
        while (!$failure) {
            $colorValue = getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
            if ($colorValue > self::$whiteThreshold) {
                break;
            }
            $y++; // Move down
        }

        // Starting from a dark enough value, move down 1px at a time until you hit a light enough value
        $topValue = 0;
        while (!$failure) {
            $colorValue = getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
            if ($colorValue < self::$whiteThreshold) {
                $topValue = $y;
                break;
            }
            $y++; // Move down
        }

        // Move down 400 because most holes are ~330p and we want to be below any normal sprokets
        $y += 400;

        // Starting from below where the sprocket should end move up until you hit a light enough value
        $bottomValue = 0;
        while (!$failure) {
            $colorValue = getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
            if ($colorValue < self::$whiteThreshold) {
                $bottomValue = $y;
                break;
            }
            $y--; // Move up
        }

        $distance = $bottomValue - $topValue;
        if ($distance < 300) {
            // print('ERROR! Detected sproket hole too small.' . PHP_EOL);
            return 0;
        }
    
        if ($distance > 399) {
            // print('ERROR! Detected sproket hole too big.' . PHP_EOL);
            return 0;
        }

        return round(($topValue + $bottomValue) / 2);
    }

    protected static function getAverageColor($imageResource, int $x, int $y, bool &$failure): int {
        $rgb = imagecolorat($imageResource, $x, $y);
        if(empty($rgb)) {
            $failure = true;
            return 0;
        }
    
        $colors = imagecolorsforindex($imageResource, $rgb);
        return (256 * 3) - $colors['red'] - $colors['green'] - $colors['blue'];
    }
}