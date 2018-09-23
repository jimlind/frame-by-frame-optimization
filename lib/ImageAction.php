<?php

use \Helpers\FileSystemHelper;
use \Locators\DarkBorderLocator;
use \Locators\SproketLocator;

class ImageAction {

    // This shouldn't really change, seems to be good
    const X_POSITION = 550;

    protected $inputPath = '';

    protected $outputPath = '';

    public function __construct(string $inputPath, string $outputPath) {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
    }

    public function run() {
        $cacheKey = $this->fixDistort($this->inputPath);
        $tmpFile = $this->writePositioningImage($cacheKey);
        
        $dataModel = new \Models\ImageDataModel($tmpFile);
        $locator = new DarkBorderLocator($dataModel);
        $data = $locator->locate();

        $sproketLocator = new SproketLocator($dataModel);
        $sproketValue = $sproketLocator->locate();

        $this->writeCroppedImage($cacheKey, $data['top'], $this->outputPath);

        print_r([
            'border top' => $data['top'],
            'sproket center' => $sproketValue,
        ]);
    }

    protected function fixDistort(string $imageFile) : string {
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