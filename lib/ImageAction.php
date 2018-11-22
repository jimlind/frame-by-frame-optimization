<?php

use \Helpers\FileSystemHelper;
use \Helpers\MathHelper;
use \Locators\DarkBorderLocator;
use \Locators\SproketLocator;
use \Locators\TopSlopeLocator;
use \Locators\BottomSlopeLocator;
use \Models\ImageDataModel;

class ImageAction {

    // This shouldn't really change, seems to be good
    const X_POSITION = 550;

    public $keepPositioningImage = false;

    public $previousImageDataModel = null;

    protected $inputPath = '';

    protected $outputPath = '';

    protected $im = null;

    public function __construct(string $inputPath, string $outputPath) {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
        $this->im = new Imagick();
    }

    public function run(): ImageDataModel {
        // Updates the global $im
        $this->fixDistort($this->inputPath);

        $this->im->setImageFormat('bmp');
        $imageBlob = $this->im->getImageBlob();
        $resource = imagecreatefromstring($imageBlob);

        $dataModel = new \Models\ImageDataModel($resource);

        $sproketLocator = new SproketLocator($dataModel);
        $dataModel->ySprocketValue = $sproketLocator->locate();

        if ($dataModel->ySprocketValue === 0) {
            echo 'NO SPROCKET FOUND ON ' . $this->inputPath . PHP_EOL;
            return $dataModel;
        }

        $darkBorderLocator = new DarkBorderLocator($dataModel);
        $darkBorderData = $darkBorderLocator->locate();
        $dataModel->yDarkTopValue = $darkBorderData['top'];
        $dataModel->yDarkBottomValue = $darkBorderData['bottom'];

        $topLocator = new TopSlopeLocator($dataModel);
        $dataModel->yCalculatedTopValue = $topLocator->locate();

        $bottomLocator = new BottomSlopeLocator($dataModel);
        $dataModel->yCalculatedBottomValue = $bottomLocator->locate();

        $halfHeight = ($dataModel->yCalculatedBottomValue - $dataModel->yCalculatedTopValue) / 2;
        $validModel = $dataModel->hasValidTopAndBottomCalculations();

        $previousHalfHeight = ($this->previousImageDataModel->yCalculatedBottomValue - $this->previousImageDataModel->yCalculatedTopValue) / 2;
        $previousValidModel = $this->previousImageDataModel->hasValidTopAndBottomCalculations();

        if ($validModel) {
            // Top and bottom points were found
            $values = [$dataModel->yCalculatedTopValue, $dataModel->yCalculatedBottomValue];
            $midPoint = MathHelper::average($values, true);
            $found = 'top & bottom';
        } elseif ($dataModel->yCalculatedTopValue !== 0 && $previousValidModel) {
            // Top point was found
            $midPoint = $dataModel->yCalculatedTopValue + $previousHalfHeight;
            $found = 'top';
        } elseif ($dataModel->yCalculatedBottomValue !== 0 && $previousValidModel) {
            // Bottom point was found
            $midPoint = $dataModel->yCalculatedBottomValue - $previousHalfHeight;
            $found = 'bottom';
        } elseif ($previousValidModel) {
            // No points found
            $topDifference = $this->previousImageDataModel->yCalculatedTopValue - $this->previousImageDataModel->ySprocketValue;
            $midPoint = $dataModel->ySprocketValue + $topDifference + $previousHalfHeight;
            $found = 'sprocket adjusted';
        } else {
            $midPoint = $dataModel->ySprocketValue + 555; // Optimized for Folder 5
            $found = 'sprocket raw';
        }

        echo 'Crop created using data from '. $found . PHP_EOL;
        $this->writeCroppedImage(round($midPoint) - 500, $this->outputPath);

        return $dataModel;
    }

    protected function fixDistort(string $imageFile) {
        // Setup the points to use for distortion writing
        $pp = [
            560,610,
            560,610,

            560,1650,
            560,1650,

            1960,1635,
            1960,1650,

            1960,610,
            1960,610,
        ];

        $this->im->readImage($imageFile);
        $this->im->distortImage(Imagick::DISTORTION_BARREL, [0.0, -0.03, 0.0, 1.03], true);
        $this->im->distortImage(Imagick::DISTORTION_PERSPECTIVE, $pp, true);
    }

    protected function writeCroppedImage(int $yPosition, string $croppedFile) {
        FileSystemHelper::md(dirname($croppedFile));

        $this->im->cropImage(1500, 1100, self::X_POSITION, $yPosition);
        $this->im->sharpenImage(0, 2);
        $this->im->setcompressionquality(100);
        $this->im->writeimage($croppedFile);

        echo $croppedFile.' written'.PHP_EOL;

    }
}