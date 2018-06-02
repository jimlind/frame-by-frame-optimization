<?php
class Position {
    protected static $blackThreshold = 800;
    protected static $relevantPixelQuantity = 10; // Keep this an even number for easier math later
    protected static $whiteThreshold = 50;
    protected static $sproketXValue = 400;

    const DARK = 'DARK';
    const LIGHT = 'LIGHT';

    public static function getY(string $imageFile): int {
        // Load up the image once, it is fast, but still only do it once
        $imageResource = imagecreatefromjpeg($imageFile);
        // Look for a middle of the sproket to use later
        $sproketInfo = self::findSproketInfo($imageResource);
        // Attempt to find the bottom of the top black border frame
        $borderBottom = self::findBlackBorderBottom($imageResource, $sproketInfo);
        
        $sproketMiddle = self::avg($sproketInfo);
        $borderToSproketDiff = abs($borderBottom - $sproketMiddle);
        if ($sproketMiddle && $borderBottom && $borderToSproketDiff < 100 && true) {
            print_r('Using border bottom.' . PHP_EOL);
            return $borderBottom - 33;
        }

        if ($sproketMiddle) {
            print_r('Using sprocket middle.' . PHP_EOL);
            return $sproketMiddle + 31;
        }

        // print_r('Nothing good detected.' . PHP_EOL);
        return 0;
    }

    protected static function findBlackBorderBottom($imageResource, array $sproketInfo): int {
        $failure = false;
        $colorList = [];
        $width = imagesx($imageResource) - 1;
        $yPosition = $sproketInfo['top'];
        while (!$failure && $yPosition <= $sproketInfo['bottom']) {
            $colorList[$yPosition] = self::getAverageColor($imageResource, $width, $yPosition, $failure);
            $yPosition++;
        }
        // The first darkest key
        $darkestValue = max($colorList);
        $index = array_search($darkestValue, $colorList);

        $lightValues = [];
        while (self::needsMoreLightValues($lightValues)) {
            $colorValue = $colorList[$index];
            if ($colorValue < ($darkestValue - 100)) {
                $lightValues[$index] = $colorValue;
            }
            $index++;
        }
        $borderBottom = $index - self::$relevantPixelQuantity;

        if (!self::middleBorderBottomHasTransition($imageResource, $borderBottom - 10, $width - 200)) {
            return 0;
        }

        return $borderBottom;
    }

    protected static function needsMoreLightValues($colorList) {
        // Need enough values
        if (count($colorList) < self::$relevantPixelQuantity) {
            return true;
        }
        
        // Need a string of values grouped together
        $keySlice = array_slice(array_keys($colorList), self::$relevantPixelQuantity * -1);
        $keyDiff = max($keySlice) - min($keySlice);
        if ($keyDiff >= self::$relevantPixelQuantity) {
            return true;
        }

        return false;
    }

    protected static function findBlackBorderBottomDeprecated($imageResource, int $sproketMiddle): int {
        $yPosition = $sproketMiddle - 30;
        $width = imagesx($imageResource) - 1;

        $borderBottom = 0;
        $blackThreshold = self::$blackThreshold;
        while ($borderBottom <= 100 || $borderBottom >= 900) {
            $borderBottom = self::findBlackBorderBottomAtThreshold($imageResource, $yPosition, $width, $blackThreshold);
            // Try a lighter black
            $blackThreshold -= 20;

            // Don't allow anything too light tho. Give up here.
            if ($blackThreshold < 600) {
                return 0;
            }
        }

        if (!self::middleBorderBottomHasTransition($imageResource, $borderBottom, $width - 200)) {
            return 0;
        }

        return $borderBottom;
    }

    protected static function findBlackBorderBottomAtThreshold($imageResource, int $yPosition, int $width, int $blackThreshold): int {
        $colorValueList = [self::DARK => [], self::LIGHT => []];

        $failure = false;
        while (!$failure) {
            $colorValue = self::getAverageColor($imageResource, $width, $yPosition, $failure);
            if ($colorValue > $blackThreshold) {
                // Create a list of all dark pixels
                $colorValueList[self::DARK][$yPosition] = $colorValue;
            }
            if ($colorValue < $blackThreshold - 10) { // create a 10 value well
                // Create a list of all light pixels
                $colorValueList[self::LIGHT][$yPosition] = $colorValue;
            }

            if (self::isPixelQuantityMet($colorValueList)) {
                // Get the average pixel difference and calculate an offset
                // Darker borders get offset more because the relative fuzzy boarder is darker (the darker it is, the larger the y position)
                $relevantDarkPixels = array_slice($colorValueList[self::DARK], self::$relevantPixelQuantity * -1);
                $averagePixelDifference = self::avg($relevantDarkPixels) / 15;
                return $yPosition - self::$relevantPixelQuantity + $averagePixelDifference;
            }

            $yPosition++;
        }

        return 0;
    }

    protected static function isPixelQuantityMet($colorValueList) {
        if (count($colorValueList[self::DARK]) < self::$relevantPixelQuantity || count($colorValueList[self::LIGHT]) < self::$relevantPixelQuantity) {
            return false;
        }

        // Hacky but fast way of getting the last key
        end($colorValueList[self::DARK]);
        end($colorValueList[self::LIGHT]);
        if (key($colorValueList[self::DARK]) > key($colorValueList[self::LIGHT])) {
            return false;
        }
        reset($colorValueList[self::DARK]);
        reset($colorValueList[self::LIGHT]);

        if (!self::relevantKeysAreRelated($colorValueList[self::DARK])) {
            return false;
        }

        if (!self::relevantKeysAreRelated($colorValueList[self::LIGHT])) {
            return false;
        }

        return true;
    }

    protected static function relevantKeysAreRelated($list) {
        $offset = -self::$relevantPixelQuantity + 1;
        $length = self::$relevantPixelQuantity - 1;
        $middle = floor(self::$relevantPixelQuantity / 2);

        $keys = array_keys(array_slice($list, $offset, $length, true));
        $averageToEndOffset = end($keys) - self::avg($keys);

        // Here the offset from average has to be less than length because that kind of makes sense
        return ($averageToEndOffset < $length);
    }

    protected static function middleBorderBottomHasTransition($imageResource, $yStartPostion, $xPosition) {
        $failure = false;
        $yPosition = $yStartPostion;
        $yEndPosition = $yStartPostion + 20;
        $colorValueList = [];

        while (!$failure && $yPosition < $yEndPosition) {
            $colorValueList[] = self::getAverageColor($imageResource, $xPosition, $yPosition, $failure);
            $yPosition++; // Move down
        }

        $halfCount = count($colorValueList) / 2;
        $topHalfAverage = self::avg(array_slice($colorValueList, $halfCount * -1));
        $bottomHalfAverage = self::avg(array_slice($colorValueList, 0, $halfCount));

        $diff = $bottomHalfAverage - $topHalfAverage;
        return $diff > 25;
    }

    protected static function findSproketInfo($imageResource): array {
        // Starting at y=0, move down 1px at a time until you hit a dark enough value
        $y = 0;
        $failure = false;
        while (!$failure) {
            $colorValue = self::getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
            if ($colorValue > self::$whiteThreshold) {
                break;
            }
            $y++; // Move down
        }

        // Starting from a dark enough value, move down 1px at a time until you hit a light enough value
        $topValue = 0;
        while (!$failure) {
            $colorValue = self::getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
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
            $colorValue = self::getAverageColor($imageResource, self::$sproketXValue, $y, $failure);
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

        return [
            'top' => $topValue,
            'bottom' => $bottomValue,
        ];
    }

    protected static function getAverageColor($imageResource, int $x, int $y, bool &$failure): int {
        // The `imagecolorat` method generates notices and it is easier to ignore them than limit coordinate input
        // Force error reporting to ignore notices
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_NOTICE);

        $rgb = imagecolorat($imageResource, $x, $y);
        if($rgb === false) {
            $failure = true;
            return 0;
        }
        $colors = imagecolorsforindex($imageResource, $rgb);

        // Return error reporting to previous level
        error_reporting($errorLevel);
        
        return (256 * 3) - $colors['red'] - $colors['green'] - $colors['blue'];
    }

    protected static function avg($list) {
        return array_sum($list) / count($list);
    }
}