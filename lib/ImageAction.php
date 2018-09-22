<?php

use \Locators\DarkBorderLocator;

class ImageAction {

    protected $filePath = '';

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function run() {
        $cacheKey = $this->fixDistort($this->filePath);
        $tmpFile = $this->writePositioningImage($cacheKey);
        
        $dataModel = new \Models\ImageDataModel($tmpFile);
        $centerColumn = $dataModel->getCenterColumn();
        $leftColumn = $dataModel->getLeftColumn();
        $rightColumn = $dataModel->getRightColumn();

        $locator = new DarkBorderLocator($dataModel);
        $data = $locator->locate();
        print_r($data);
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
}