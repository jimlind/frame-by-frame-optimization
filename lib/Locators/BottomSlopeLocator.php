<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class BottomSlopeLocator extends SlopeLocator{

    protected $direction = -1;

    public function locate($list) {
        $this->findLargestSlope($list);
    }
}