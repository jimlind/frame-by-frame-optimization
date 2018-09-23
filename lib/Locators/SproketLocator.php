<?php
namespace Locators;

use \Helpers\MathHelper;
use \Models\ImageDataModel;

class SproketLocator {

    const Y_SPROCKET_MIN = 300;
    const Y_SPROCKET_MAX = 400;

    protected $dataModel = null;

    public function __construct(ImageDataModel $dataModel) {
        $this->dataModel = $dataModel;
    }

    public function locate(): int {
        $originalColumn = $column = array_slice($this->dataModel->getSproketColumn(), 0, 1000, true);
        asort($column);
        $lightValues = array_slice($column, 20, 260, true);
        $sproketMax = max($lightValues);

        $first = 0;
        foreach ($originalColumn as $key => $value) {
            if ($value === $sproketMax) {
                $first = $key;
                break;
            }
        }

        $last = 0;
        foreach (array_reverse($originalColumn, true) as $key => $value) {
            if ($value === $sproketMax) {
                $last = $key;
                break;
            }
        }

        if (!MathHelper::between($last - $first, self::Y_SPROCKET_MIN, self::Y_SPROCKET_MAX)) {
            return 0;
        }

        return MathHelper::average([$first, $last], true);
    }
}