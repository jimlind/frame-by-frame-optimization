<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class DarkBorderLocator {

    protected $dataModel = null;

    protected $sproketY = 0;

    public function __construct(ImageDataModel $dataModel, int $sproketY) {
        $this->dataModel = $dataModel;
        $this->sproketY = $sproketY;
    }

    public function locate() {
        $brightnessList = $this->dataModel->getCenterColumn();
        $middleY = round(count($brightnessList) / 2);

        $topHalfList = array_slice($brightnessList, $this->sproketY - 100, 200, true);
        // TODO: FIND A WAY TO APPROXIMATE THE BOTTOM HALF
        $bottomHalfList = array_slice($brightnessList, $middleY + 1, -1, true);
        
        return [
            'top' => $this->findViablePosition($topHalfList),
            'bottom' => 0, // TODO: SOMETHING HERE $this->findViablePosition($bottomHalfList),
        ];
    }

    protected function findViablePosition(array $valueList): int {
        $result = $this->findDarkestBlock($valueList);
        if ($result) {
            return $result;
        }

        $result = $this->findLargeDarkBlock($valueList);
        if ($result) {
            return $result;
        }

        return 0;
    }

    protected function findDarkestBlock(array $valueList): int {
        asort($valueList);
        $valueSlice = array_slice($valueList, 0, 60, true);
        ksort($valueSlice);
        $valueSlice = array_slice($valueSlice, 10, -10, true);

        $sliceLength = count($valueSlice);
        reset($valueSlice);
        $firstLocation = key($valueSlice);
        end($valueSlice);
        $lastLocation = key($valueSlice);

        if ($sliceLength / ($lastLocation - $firstLocation) <= 0.5) {
            return 0;
        }

        return MathHelper::average(array_keys($valueSlice), true);
    }

    protected function findLargeDarkBlock(array $valueList): int {
        $min = min($valueList);
        while ($min < 20) {
            $filteredList = array_filter($valueList, function ($a) use ($min) { return ($a <= $min);}); 
            $newList = $newListCount = $keyList = [];
            $prevKey = 0;
            foreach ($filteredList as $key => $value) {
                if ($key === $prevKey + 1) {
                    $keyList[] = $key;
                } else {
                    $newList[] = $keyList;
                    $newListCount[] = count($keyList);
                    $keyList = [$key];
                }
                $prevKey = $key;
            }

            $maxCount = max($newListCount);
            if ($maxCount > 20) {
                $index = array_search($maxCount, $newListCount);
                return MathHelper::average($newList[$index]);
            }

            $min += 0.1;
        }

        return 0;
    }
}