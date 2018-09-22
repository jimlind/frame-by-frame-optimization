<?php
class PositionFourCorners {
    // This is basically the X of a left-ish and right-ish parts of the frame are.
    const X_LEFT_VALUE = 700;
    const X_RIGHT_VALUE = 1800;

    // This is how far apart we should be looking for a bright to dark change
    const Y_DIFFERENCE_DISTANCE = 12;

    // Largest area we should search for a border
    const Y_BORDER_TARGET_MAX = 80;

    // How much wiggle room do we want to allow from our darkest color
    const DARKNESS_THRESHOLD = 50;

    // Determine upper and lower bounds for frame size
    const FRAME_MIN_SIZE = 970;
    const FRAME_MAX_SIZE = 1000;

    // How far the center of the sprocket hole is from the top of the frame
    // MIGHT NEED TO BE DYNAMICALLY CALCULATED
    const SPROCKET_OFFSET = 40;

    public static function getY(string $imageFile): int {
        // Load up the image once, it is fast, but still only do it once
        $imageResource = imagecreatefromjpeg($imageFile);

        // Get the complete list of "brightness" values for the left column then process it looking for appropriate brightness changes
        $leftColorValueList = self::gatherColorBrightnessList($imageResource, self::X_LEFT_VALUE);
        $leftPositions = self::processColorValueList($leftColorValueList);

        // Get the complete list of "brightness" values for the right column then process it looking for appropriate brightness changes
        $rightColorValueList = self::gatherColorBrightnessList($imageResource, self::X_RIGHT_VALUE);
        $rightPositions = self::processColorValueList($rightColorValueList);

        $yFromFrame = self::findPositionWithFrame($leftPositions, $rightPositions);
        if (MathHelper::in($yFromFrame, 550, 650)) { // MAGIC VALUES SHOULD BE CALCULATED FROM SPROKET
            print('Y value found with frame.' . PHP_EOL);
            return $yFromFrame;
        }

        $yFromTop = self::findPositionWithTop($leftPositions, $rightPositions);
        if (MathHelper::in($yFromTop, 550, 650)) { // MAGIC VALUES SHOULD BE CALCULATED FROM SPROKET
            print('Y value found with top.' . PHP_EOL);
            return $yFromTop;
        }

        $dataFromLargestSlope = self::findPositionWithSlope($leftPositions, $rightPositions);
        $yFromLargestSlope = $dataFromLargestSlope['yPosition'];
        if (MathHelper::in($yFromLargestSlope, 550, 650)) { // MAGIC VALUES SHOULD BE CALCULATED FROM SPROKET
            print('Y value found with largest slope ' . $dataFromLargestSlope['cornerName'] . '.' . PHP_EOL);
            return $yFromLargestSlope;
        }

        print('Y value found from sproket info.' . PHP_EOL);
        $sproketInfo = SproketFinder::getCenter($imageResource);
        return $sproketInfo + self::SPROCKET_OFFSET;
    }

    public static function getBorderData(string $imageFile): array {
        $imageResource = imagecreatefromjpeg($imageFile);
        $halfWidth = round(imagesx($imageResource)/2);
        $brightnessList = self::gatherColorBrightnessList($imageResource, $halfWidth);
        
        return BorderFinder::getBorderCenters($brightnessList);
    }

    public static function gatherPositionData(string $imageFile): array {
        $imageResource = imagecreatefromjpeg($imageFile);
        
        // $halfWidth = round(imagesx($imageResource)/2);
        // $brightnessList = self::gatherColorBrightnessList($imageResource, $halfWidth);
        // $a = BorderFinder::getBorderCenters($brightnessList);
        // print_r($a);
        // print_r(['a']);
        // die();

        // Get the complete list of "brightness" values for the left column then process it looking for appropriate brightness changes
        $leftColorValueList = self::gatherColorBrightnessList($imageResource, self::X_LEFT_VALUE);
        $leftPositions = self::processColorValueList($leftColorValueList);

        // Get the complete list of "brightness" values for the right column then process it looking for appropriate brightness changes
        $rightColorValueList = self::gatherColorBrightnessList($imageResource, self::X_RIGHT_VALUE);
        $rightPositions = self::processColorValueList($rightColorValueList);

        $yFromFrame = self::findPositionWithFrame($leftPositions, $rightPositions, true);
        $yFromTop = self::findPositionWithTop($leftPositions, $rightPositions);
        $yFromBottom = self::findPositionWithBottom($leftPositions, $rightPositions);
        if(empty($yFromFrame) || empty($yFromTop) || empty($yFromBottom) || !MathHelper::in($yFromFrame, 550, 650)) {
            return [];
        }

        $sproketCenter = SproketFinder::getCenter($imageResource);

        return [
        	'sproketDiff' => $yFromFrame - $sproketCenter,
            'topDiff' => $yFromFrame - $yFromTop,
            'bottomDiff' => $yFromFrame - $yFromBottom,
        ];
    }

    protected static function gatherColorBrightnessList($imageResource, $x):array {
        $height = imagesy($imageResource);
        $brightnessList = [];
        for ($y = 0; $y <= $height; $y++) {
            $brightnessList[$y] = ImageHelper::getColorBrightness($imageResource, $x, $y);
        }

        return $brightnessList;
    }

    protected static function processColorValueList(array $colorValueList):array {
        $middle = round(count($colorValueList) / 2);
        $topList = array_slice($colorValueList, 0, $middle, true);
        $bottomList = array_slice($colorValueList, $middle, null, true); 

        return [
            'top' => self::findLargestSlope($topList, 1),
            'bottom'=> self::findLargestSlope($bottomList, -1),
        ];
    }

    protected static function findLargestSlope(array $list, int $direction):array {
        $darkestValue = max($list);
        $index = array_search($darkestValue, $list);

        $maxDifference = $maxDifferencePosition = 0;
        for ($y = 0; $y <= self::Y_BORDER_TARGET_MAX; $y++) {
            $offsetIndexA = $index + ($y * $direction);
            $offsetIndexB = $index + (($y + self::Y_DIFFERENCE_DISTANCE) * $direction);

            if (empty($list[$offsetIndexA]) || empty($list[$offsetIndexB])) {
                // Colors aren't found for what we are looking for
                continue;
            }

            $colorA = $list[$offsetIndexA];
            if ($colorA < $darkestValue - self::DARKNESS_THRESHOLD) {
                // Darker value in the comparison isn't dark enough
                break;
            }

            $difference = abs($colorA - $list[$offsetIndexB]);
            if ($difference > $maxDifference) {
                $maxDifference = $difference;
                $maxDifferencePosition = round(MathHelper::avg([$offsetIndexA, $offsetIndexB]));
            }
        }

        return [
            'position' => $maxDifferencePosition,
            'slope' => $maxDifference,
        ];
    }

    protected static function findPositionWithFrame($leftPositions, $rightPositions, $forceFourCorners = false): int {
        $validTopPositions = [];

        $leftHeight = $leftPositions['bottom']['position'] - $leftPositions['top']['position'];
        if (MathHelper::in($leftHeight, self::FRAME_MIN_SIZE, self::FRAME_MAX_SIZE)) {
            $validTopPositions[] = $leftPositions['top']['position'];
        }
        
        $rightHeight = $rightPositions['bottom']['position'] - $rightPositions['top']['position'];
        if (MathHelper::in($rightHeight, self::FRAME_MIN_SIZE, self::FRAME_MAX_SIZE)) {
            $validTopPositions[] = $rightPositions['top']['position'];
        }
        
        $forwardHeight = $leftPositions['bottom']['position'] - $rightPositions['top']['position'];
        if (MathHelper::in($forwardHeight, self::FRAME_MIN_SIZE, self::FRAME_MAX_SIZE)) {
            $validTopPositions[] = $rightPositions['top']['position'];
        }

        $backwardHeight = $rightPositions['bottom']['position'] - $leftPositions['top']['position'];
        if (MathHelper::in($backwardHeight, self::FRAME_MIN_SIZE, self::FRAME_MAX_SIZE)) {
            $validTopPositions[] = $leftPositions['top']['position'];
        }

        if ($forceFourCorners && count($validTopPositions) !== 4) {
            return 0;
        }

        return round(MathHelper::avg($validTopPositions));
    }

    protected static function findPositionWithTop($leftPositions, $rightPositions): int {
        $rightTop = $rightPositions['top']['position'];
        $leftTop = $leftPositions['top']['position'];

        if (abs($rightTop - $leftTop) > 10) { // MAGIC VALUE SHOULD BE CONSTANT
            return 0;
        }

        return round(MathHelper::avg([$rightTop, $leftTop]));
    }

    protected static function findPositionWithBottom($leftPositions, $rightPositions): int {
        $rightBottom = $rightPositions['bottom']['position'];
        $leftBottom = $leftPositions['bottom']['position'];

        if (abs($rightBottom - $leftBottom) > 10) { // MAGIC VALUE SHOULD BE CONSTANT
            return 0;
        }

        return round(MathHelper::avg([$rightBottom, $leftBottom]));
    }

    protected static function findPositionWithSlope($leftPositions, $rightPositions): array {    
        $greatestSlope = $yPosition = 0;
        $cornerName = '';

        if ($leftPositions['top']['slope'] > $greatestSlope) {
            $greatestSlope = $leftPositions['top']['slope'];
            $yPosition = $leftPositions['top']['position'] - 0;
            $cornerName = "top left";
        }

        if ($rightPositions['top']['slope'] > $greatestSlope) {
            $greatestSlope = $rightPositions['top']['slope'];
            $yPosition = $rightPositions['top']['position'] - 0;
            $cornerName = "top right";
        }

        if ($leftPositions['bottom']['slope'] > $greatestSlope) {
            $greatestSlope = $leftPositions['bottom']['slope'];
            $yPosition = $leftPositions['bottom']['position'] - 989; // MAGIC VALUE SHOULD BE CONSTANT
            $cornerName = "bottom left";
        }

        if ($rightPositions['bottom']['slope'] > $greatestSlope) {
            $yPosition = $rightPositions['bottom']['position'] - 989; // MAGIC VALUE SHOULD BE CONSTANT
            $cornerName = "bottom right";
        }

        return [
            'yPosition' => $yPosition,
            'cornerName' => $cornerName,
        ];
    }
}