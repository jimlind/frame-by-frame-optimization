<?php
class SproketFinder {
    // This is basically the center of where the sproket appears
    const X_VALUE = 400;

    // This is how far apart we should be looking for a bright to dark change
    const Y_DIFFERENCE_DISTANCE = 12;

    public static function getCenter($imageResource): int {
        $highestDifference = $highestDifferencePosition = 0;
        $lowestDifference = $lowestDifferencePosition = 0;
        for ($y = 0; $y <= 1000; $y++) {
            $topColorValue = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y);
            $bottomColorValue = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y + self::Y_DIFFERENCE_DISTANCE);
            if ($topColorValue === -1 || $bottomColorValue === -1) {
                continue;
            }

            $difference = $topColorValue - $bottomColorValue;
            if ($difference > $highestDifference) {
                $highestDifference = $difference;
                $highestDifferencePosition = $y + floor(self::Y_DIFFERENCE_DISTANCE / 2);
            }
            if ($difference < $lowestDifference) {
                $lowestDifference = $difference;
                $lowestDifferencePosition = $y + floor(self::Y_DIFFERENCE_DISTANCE / 2);
            }
        }

        print_r([$highestDifference, $lowestDifference]);
        print_r([$highestDifferencePosition, $lowestDifferencePosition]);
        die();

        // Starting from a dark enough value, move down 1px at a time until you hit a light enough value
        $topValue = 0;
        while (!$failure) {
            $colorValue = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y, $failure);
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
            $colorValue = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y, $failure);
            if ($colorValue < self::$whiteThreshold && !$failure) {
                $bottomValue = $y;
                break;
            }
            $y--; // Move up
        }

        $distance = $bottomValue - $topValue;
        if ($distance < 300) {
            // print('ERROR! Detected sproket hole too small.' . PHP_EOL);
            return ['top' => 0, 'bottom' => 0];
        }
    
        if ($distance > 400) {
            // print('ERROR! Detected sproket hole too big.' . PHP_EOL);
            return ['top' => 0, 'bottom' => 0];
        }

        return [
            'top' => $topValue,
            'bottom' => $bottomValue,
        ];
    }

    public static function getBlackAndWhite($imageResource):array {
        $colorQuantityList = [];
        for ($y = 0; $y <= 1000; $y++) {
            $colorValue = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y);
            if (empty($colorQuantityList[$colorValue])) {
                $colorQuantityList[$colorValue] = 1;
            } else {
                $colorQuantityList[$colorValue]++;
            }
        }
        
        $blackCount = $blackBrightness = 0;
        $whiteCount = $whiteBrightness = 0;

        foreach ($colorQuantityList as $color => $quantity) {
            if ($color > ImageHelper::GRAY) {
                if ($quantity > $blackCount) {
                    $blackCount = $quantity;
                    $blackBrightness = $color;
                }
            } else {
                if ($quantity > $whiteCount) {
                    $whiteCount = $quantity;
                    $whiteBrightness = $color;
                }
            }
        }

        return [
            'black' => $blackBrightness,
            'white' => $whiteBrightness,
        ];
    }
}