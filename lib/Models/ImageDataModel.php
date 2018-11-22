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

    public $yCalculatedTopValue = 0;
    public $yCalculatedBottomValue = 0;

    public $lightToDarkDifference = 2;

    protected $resource = null;

    protected $centerColumn = [];

    protected $leftColumn = [];

    protected $rightColumn = [];

    protected $sproketColumn = [];

    protected $compositeColumn = [];

    public function __construct($resource = null) {
        // Allow this to silently fail if we have a bad image path
        $this->resource = $resource;
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

    public function hasValidTopAndBottomCalculations(): bool {
        return ($this->yCalculatedTopValue !== 0 && $this->yCalculatedBottomValue !== 0);
    }

    public function getTopBorderColumn(): array {
        return $this->getCompositeColumn($this->ySprocketValue - 100, 1, 200);
    }

    public function getBottomBorderColumn(): array {
        return $this->getCompositeColumn($this->ySprocketValue + 1000, 1, 200);
    }

    public function getTopSlopeColumn(): array {
        return $this->getCompositeColumn($this->yDarkTopValue, 1, 100);
    }

    public function getBottomSlopeColumn(): array {
        return $this->getCompositeColumn($this->yDarkBottomValue, -1, 100);
    }

    private function getCompositeColumn(int $startingY, int $direction, int $size): array {
        $valueList = [];
        foreach (range($startingY, $startingY + ($size * $direction)) as $y) {
            $valueList[$y] = ImageHelper::getRowAverageBrightness($this->resource, self::X_LEFT_VALUE, self::X_RIGHT_VALUE, $y);
        }

        return $valueList;
    }
}