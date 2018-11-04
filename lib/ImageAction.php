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

    public function __construct(string $inputPath, string $outputPath) {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
    }

    public function run(): ImageDataModel {
        $cacheKey = $this->fixDistort($this->inputPath);

        $tmpFilePath = '';
        if ($this->keepPositioningImage) {
            $outputDir = dirname($this->outputPath);
            FileSystemHelper::md($outputDir);
            $tmpFilePath = implode([
                $outputDir,
                DIRECTORY_SEPARATOR,
                '_',
                basename($this->outputPath)
            ]);
        }
        $tmpFile = $this->writePositioningImage($cacheKey, $tmpFilePath);
        
        $dataModel = new \Models\ImageDataModel($tmpFile);

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

        // Debug Data
        print_r([
            $dataModel->ySprocketValue,
            $dataModel->yCalculatedBottomValue,
            $dataModel->yCalculatedTopValue,
            $darkBorderData,
        ]);

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
            $midPoint = $dataModel->ySprocketValue - $topDifference - $previousHalfHeight;
            $found = 'sprocket adjusted';
        } else {
            $midPoint = $dataModel->ySprocketValue + 555; // Optimized for Folder 5
            $found = 'sprocket raw';
        }

        echo 'Crop created using data from '. $found .PHP_EOL;
        $this->writeCroppedImage($cacheKey, $midPoint - 500, $this->outputPath);

        return $dataModel;
    }

    protected function fixDistort(string $imageFile): string {
        // Setup the points to use for distortion writing
        $perspectivePoints = [
            '560,610 560,610',
            '560,1650 560,1650',
            '1960,1635 1960,1650',
            '1960,610 1960,610',
        ];
        
        // Fix distorition and write to cache
        $cacheKey = 'mpc:tmp';
        $fixDistortCommand = [
            'convert',
            $imageFile,
            '-distort Barrel "0.0 -0.03 0.0 1.03"',
            '-write mpr:distort',
            '+delete',
            '\( mpr:distort',
            '-distort Perspective "'.implode(' ', $perspectivePoints).'"',
            '+write',
            $cacheKey,
            '\) null:',
        ];
        shell_exec(implode(' ', $fixDistortCommand));

        return $cacheKey;
    }

    protected function writePositioningImage(string $cacheKey, string $tmpFile = '') : string{
        // If nothing was supplied.. use a tmp file
        if (empty($tmpFile)) {
            $tmpFile = sys_get_temp_dir() . '/film-sprocket-hole.jpg';
        }

        // Perform conversion
        $cmd = [
            'convert',
            $cacheKey,
            '-quality 92',
            $tmpFile
        ];
        shell_exec(implode(' ', $cmd));

        return $tmpFile;
    }

    protected function writeCroppedImage(string $cacheKey, int $yPosition, string $croppedFile) {
        FileSystemHelper::md(dirname($croppedFile));
        
        // Finalize the image and write to disk
        $cropCommand = [
            'convert',
            $cacheKey,
            '-crop 1500x1100+' . self::X_POSITION . '+' . $yPosition,
            '-sharpen 0x2',
            '-quality 100',
            $croppedFile,
        ];
        shell_exec(implode(' ', $cropCommand));
        //print_r(implode(' ', $cropCommand) . PHP_EOL);

        echo $croppedFile.' written'.PHP_EOL;

    }
}