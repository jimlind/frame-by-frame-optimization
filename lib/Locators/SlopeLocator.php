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

    protected $list = [];

    public function __construct(ImageDataModel $dataModel) {
        $this->dataModel = $dataModel;
        $this->startingPosition = $dataModel->yDarkTopValue;
    }

    public function locate():int {
        $average = MathHelper::average(array_slice($this->list, 0, 10));

        if (($average - MathHelper::average($this->list)) < 0.7) {
            echo 'Nothing interested found with direction: ' . $this->direction . PHP_EOL;
            return 0;
        }

        $filteredList = array_filter($this->list, function($value) use ($average) {
            return $value > $this->getDifference($average);
        });

        $filteredKeys = array_keys($filteredList);
        foreach($filteredKeys as $key => $value) {
            $diff = ($filteredKeys[$key + (6 * $this->direction)] ?? 0) - $value;
            if ($diff === 6) {
                return $value;
            }
        }
        return 0;
    }

    private function getDifference(float $average): float {
        // DIRECTORY 4 => (1.24 * $average) + 0.3;
        // DIRECTORY 5 => (1.3 * $average) + 0.3;
        return (1.24 * $average) + 0.3;
    }
}