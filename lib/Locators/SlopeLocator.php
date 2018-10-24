<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class SlopeLocator {

    const Y_SPROCKET_MIN = 300;
    const Y_SPROCKET_MAX = 400;

    protected $dataModel = null;

    protected $startingPosition = 0;

    protected $direction = 0;

    public function __construct(ImageDataModel $dataModel) {
        $this->dataModel = $dataModel;
        $this->startingPosition = $dataModel->yDarkTopValue;
    }

    protected function findLargestSlope(array $list, int $direction):array {
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

}