<?php
namespace Models;

use \Helpers\ImageHelper;
use \Helpers\MathHelper;

class ImageDataModel {
    // This is basically the X of a left-ish and right-ish parts of the frame are.
    const X_SPROKET_VALUE = 400;
    const X_LEFT_VALUE = 700;
    const X_RIGHT_VALUE = 1800;

    public $ySprocketValue = 0;

    public $yDarkTopValue = 0;
    public $yDarkBottomValue = 0;

    protected $resource = null;

    protected $centerColumn = [];

    protected $leftColumn = [];

    protected $rightColumn = [];

    protected $sproketColumn = [];

    protected $compositeColumn = [];
    
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
            $this->leftColumn = ImageHelper::gatherColorBrightnessList($this->resource, self::X_LEFT_VALUE);
        }

        return $this->leftColumn;
    }

    public function getRightColumn(): array {
        if (empty($this->rightColumn)) {
            $this->rightColumn = ImageHelper::gatherColorBrightnessList($this->resource, self::X_RIGHT_VALUE);
        }

        return $this->rightColumn;
    }

    public function getSproketColumn(): array {
        if (empty($this->sproketColumn)) {
            $this->sproketColumn = ImageHelper::gatherColorBrightnessList($this->resource, self::X_SPROKET_VALUE);
        }

        return $this->sproketColumn;
    }

    public function getTopCompositeColumn(): array {
        return $this->getCompositeColumn($this->yDarkTopValue, 1);
    }

    public function getBottomCompositeColumn(): array {
        return $this->getCompositeColumn($this->yDarkBottomValue, -1);
    }

    private function getCompositeColumn(int $startingY, int $direction): array {
        $valueList = [];
        foreach (range($startingY, $startingY + (100 * $direction)) as $y) {
            $valueList[$y] = ImageHelper::getRowAverageBrightness($this->resource, self::X_LEFT_VALUE, self::X_RIGHT_VALUE, $y);
        }

        return $valueList;
    }
}