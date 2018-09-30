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

        $d = array_filter([$a, $b, $c]);
        //print_r([$a,$b,$c]);

        return MathHelper::median($d);
    }

    public function x($list) {
        $pa = array_slice($list, $this->startingPosition, 10);
        $pv = MathHelper::average($pa) + 3;

        $s = array_slice($list, $this->startingPosition, 80, true);
        foreach($s as $k => $v) {
            if ($v > $pv) {
                return $k;
            }
        }
        return 0;
    }

    public function z($list) {
        $slopeList = [];
        for($a = 0; $a <= 80; $a += 10) {
            $p = $this->startingPosition + $a;
            $j = MathHelper::average(array_slice($list, $p - 1, 3));
            $k = MathHelper::average(array_slice($list, $p + 9, 3));
            $slopeList[$p] = ($k - $j);
        }
        print_r($slopeList);
        $index = array_search(max($slopeList), $slopeList);
        $slopeList = [];
        for($a = -2; $a <= 12; $a++) {
            $p = $index + $a;
            $j = MathHelper::average(array_slice($list, $p - 1, 3));
            $k = MathHelper::average(array_slice($list, $p + 9, 3));
            $slopeList[$p] = ($k - $j);
        }
        $index = array_search(max($slopeList), $slopeList);

        return $index;
    }

    public function y($list) {
        $listSlice = array_slice($list, $this->startingPosition, 80, true);
        print_r($listSlice);
        $prevValue = 0;
        $slopeList = [];
        $slopeListX = [];
        foreach ($listSlice as $key => $value) {
            if ($key % 10 !== 0) {
                continue;
            }
            if ($prevValue) {
                $valueX = MathHelper::average(array_slice($listSlice, $key-1, 3));
                $prevValueX = MathHelper::average(array_slice($listSlice, $key-11, 3));
                $slopeListX[$key - 10] = $valueX - $prevValueX;

                $slopeList[$key - 10] = $value - $prevValue;
            }
            $prevValue = $value;
        }
        print_r($slopeListX);
        print_r($slopeList);
        $index = array_search(max($slopeList), $slopeList);

        $prevValue = $listSlice[$index];
        $slopeList = [];
        for($a = 1; $a < 10; $a++) {
            $nextIndex = $index + $a;
            $nextValue = $listSlice[$nextIndex];
            $slopeList[$nextIndex] = $nextValue - $prevValue;
            $prevValue = $nextValue;
        }
        print_r($slopeList);
        $index = array_search(max($slopeList), $slopeList);

        return $index;
    }
}