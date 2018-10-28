<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class DarkBorderLocator {

    protected $dataModel = null;

    protected $sproketY = 0;

    public function __construct(ImageDataModel $dataModel) {
        $this->dataModel = $dataModel;
        $this->sproketY = $dataModel->ySprocketValue;
    }

    public function locate() {
        $brightnessList = $this->dataModel->getCenterColumn();
        $middleY = round(count($brightnessList) / 2);

        // print_r([

        // ]);

        $topHalfList = array_slice($brightnessList, $this->sproketY - 100, 200, true);
        $bottomHalfList = array_slice($brightnessList, $this->sproketY + 1000, 200, true);

        print_r([
            $topHalfList,
            $bottomHalfList,
        ]);

        $topHalfList = $this->dataModel->getTopBorderColumn();
        $bottomHalfList =  $this->dataModel->getBottomBorderColumn();

        return [
            'top' => $this->findViablePosition($topHalfList),
            'bottom' => $this->findViablePosition($bottomHalfList),
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

        // If the dark block we found was too small, exit
        if ($sliceLength / ($lastLocation - $firstLocation) <= 0.5) {
            return 0;
        }

        return MathHelper::average(array_keys($valueSlice), true);
    }

    protected function findLargeDarkBlock(array $valueList): int {
        $min = min($valueList);
        $a = [];
        while ($min < 20) {
            $a[] = $min;
            $filteredList = array_filter($valueList, function ($a) use ($min) { return ($a <= $min);}); 
            //print_r($filteredList);
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

            print_r([$newList, $newListCount]);

            $maxCount = max($newListCount);
            if ($maxCount > 10) {
                $index = array_search($maxCount, $newListCount);
                print_r($a);
                return MathHelper::average($newList[$index]);
            }

            $min += 0.1;
        }

        return 0;
    }
}