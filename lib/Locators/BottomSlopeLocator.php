<?php
namespace Locators;

use \Models\ImageDataModel;

class BottomSlopeLocator extends SlopeLocator{

    protected $direction = -1;

    public function __construct(ImageDataModel $dataModel) {
        parent::__construct($dataModel);
        $this->list = $this->dataModel->getBottomCompositeColumn();
    }
}