<?php
class SproketFinder {
    // This is basically the X center of where the sproket should appear
    const X_VALUE = 400;

    // Target area of Y where the sproket should appear
    const Y_SPROCKET_TARGET_MAX = 1000;

    // This is how far apart we should be looking for a bright to dark change
    const Y_DIFFERENCE_DISTANCE = 12;

    // Sizes we allow
    const Y_SPROCKET_MAX = 400;
    const Y_SPROCKET_MIN = 300;

    public static function getCenter($imageResource): int {
        $brightnessList = self::gatherColorBrightnessList($imageResource);
        $highestDifference = $highestDifferencePosition = 0;
        $lowestDifference = $lowestDifferencePosition = 0;

        foreach ($brightnessList as $key => $value) {
            $nextValue = $brightnessList[$key + self::Y_DIFFERENCE_DISTANCE] ?? $value;
            $difference = $value - $nextValue;
            if ($difference > $highestDifference) {
                $highestDifference = $difference;
                $highestDifferencePosition = $key + floor(self::Y_DIFFERENCE_DISTANCE / 2);
            }
            if ($difference < $lowestDifference) {
                $lowestDifference = $difference;
                $lowestDifferencePosition = $key + floor(self::Y_DIFFERENCE_DISTANCE / 2);
            }
        }

        $ySize = $lowestDifferencePosition - $highestDifferencePosition;
        if ($ySize > self::Y_SPROCKET_MAX) {
            return -1;
        } elseif ($ySize < self::Y_SPROCKET_MIN) {
            return -1;
        }

        print_r([
            'top' => $highestDifferencePosition,
            'bottom' => $lowestDifferencePosition,
        ]);

        $middle = MathHelper::avg([$lowestDifferencePosition, $highestDifferencePosition]);
        return round($middle);
    }

    protected static function gatherColorBrightnessList($imageResource):array {
        $brightnessList = [];
        for ($y = 0; $y <= self::Y_SPROCKET_TARGET_MAX; $y++) {
            $brightnessList[$y] = ImageHelper::getColorBrightness($imageResource, self::X_VALUE, $y);;
        }

        return $brightnessList;
    }
}