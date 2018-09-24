<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class TopSlopeLocator extends SlopeLocator{

    protected $direction = 1;

    public function locate() {
        $a = $this->x($this->dataModel->getLeftColumn());
        $b = $this->x($this->dataModel->getCenterColumn());
        $c = $this->x($this->dataModel->getRightColumn());

        return MathHelper::average([$a, $b, $c]);
    }

    public function x($list) {
        $listSlice = array_slice($list, $this->startingPosition, 80, true);

        $prevValue = 0;
        $slopeList = [];
        foreach ($listSlice as $key => $value) {
            if ($key % 10 !== 0) {
                continue;
            }
            if ($prevValue) {
                $slopeList[$key - 10] = $value - $prevValue;
            }
            $prevValue = $value;
        }
        $index = array_search(min($slopeList), $slopeList);

        $prevValue = $listSlice[$index];
        $slopeList = [];
        for($a = 1; $a < 10; $a++) {
            $nextIndex = $index + $a;
            $nextValue = $listSlice[$nextIndex];
            $slopeList[$nextIndex] = $nextValue - $prevValue;
            $prevValue = $nextValue;
        }
        $index = array_search(min($slopeList), $slopeList);

        return $index;
    }
}