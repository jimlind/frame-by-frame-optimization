<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class TopSlopeLocator extends SlopeLocator{

    protected $direction = 1;

    public function locate():int {
        $list = $this->dataModel->getCompositeColumn();
        $average = MathHelper::average(array_slice($list, 0, 10));

        $filteredList = array_filter($list, function($value) use ($average) {
            return $value > $average * 1.5;
        });

        $filteredKeys = array_keys($filteredList);
        foreach($filteredKeys as $key => $value) {
            $diff = ($filteredKeys[$key + 6] ?? 0) - $value;
            if ($diff === 6) {
                return $value;
            }
        }
        return 0;
    }
}