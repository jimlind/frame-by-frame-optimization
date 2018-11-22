<?php

use \Models\ImageDataModel;

class ImageFolderAction {

    public $fileGlobInput = '/c*.jpeg';

    public $keepPositioningImage = false;

    public $previousImageDataModel = null;

    protected $inputPath = '';

    protected $outputPath = '';

    public function __construct(string $inputPath, string $outputPath) {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
    }

    public function run(): ImageDataModel {
        $previousImageDataModel = new ImageDataModel();
        $imageFileList = glob($this->inputPath . $this->fileGlobInput);
        foreach ($imageFileList as $imageFile) {
            $timeStart = microtime(true);
            $outputFile = $this->outputPath . DIRECTORY_SEPARATOR . basename($imageFile);

            $imageAction = new ImageAction($imageFile, $outputFile);
            $imageAction->keepPositioningImage = $this->keepPositioningImage;
            $imageAction->previousImageDataModel = $this->previousImageDataModel;
            $imageDataModel = $imageAction->run();

            $timeDiff = round(microtime(true) - $timeStart, 2);
            echo 'Image converstion took '. $timeDiff . ' seconds' . PHP_EOL;

            if ($imageDataModel->hasValidTopAndBottomCalculations()) {
                // Overwrite existing data model
                $this->previousImageDataModel = $imageDataModel;
            }
        }

        return $this->previousImageDataModel;
    }
}