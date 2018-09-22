<?php
namespace Models;

use \Helpers\ImageHelper;

class ImageDataModel {
    // This is basically the X of a left-ish and right-ish parts of the frame are.
    const X_LEFT_VALUE = 700;
    const X_RIGHT_VALUE = 1800;

    protected $resource = null;

    protected $centerColumn = [];

    protected $leftColumn = [];

    protected $rightColumn = [];
    
    public function __construct(string $imageFilePath) {
        $this->resource = imagecreatefromjpeg($imageFilePath);
    }

    public function getCenterColumn(): array {
        if (empty($this->centerColumn)) {
            $x = round(imagesx($this->resource) / 2);
            $this->centerColumn = ImageHelper::gatherColorBrightnessList($this->resource, $x);
        }

        return $this->centerColumn;
    }

    public function getLeftColumn(): array {
        if (empty($this->leftColumn)) {
            return $this->leftColumn = ImageHelper::gatherColorBrightnessList($this->resource, self::X_LEFT_VALUE);
        }

        return $this->leftColumn;
    }

    public function getRightColumn(): array {
        if (empty($this->rightColumn)) {
            return $this->rightColumn = ImageHelper::gatherColorBrightnessList($this->resource, self::X_RIGHT_VALUE);
        }

        return $this->rightColumn;
    }


}