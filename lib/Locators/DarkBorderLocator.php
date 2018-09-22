<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class DarkBorderLocator {

    protected $dataModel = null;

    public function __construct(ImageDataModel $dataModel) {
        $this->dataModel = $dataModel;
    }

    public function locate() {
        $brightnessList = $this->dataModel->getCenterColumn();
        $middleY = round(count($brightnessList) / 2);
        
        $topHalfList = array_slice($brightnessList, 1, $middleY - 1, true);
        $bottomHalfList = array_slice($brightnessList, $middleY + 1, -1, true);
        
        return [
            'top' => $this->findDarkestBlock($topHalfList),
            'bottom' => $this->findDarkestBlock($bottomHalfList),
        ];
    }

    protected function findDarkestBlock(array $valueList): int {
        arsort($valueList);
        $valueSlice = array_slice($valueList, 0, 60, true);
        ksort($valueSlice);
        $valueSlice = array_slice($valueSlice, 10, -10, true);

        $sliceLength = count($valueSlice);
        reset($valueSlice);
        $firstLocation = key($valueSlice);
        end($valueSlice);
        $lastLocation = key($valueSlice);

        if ($sliceLength / ($lastLocation - $firstLocation) > 0.5) {
            return MathHelper::average(array_keys($valueSlice), true);
        }

        return -1;
    }
}