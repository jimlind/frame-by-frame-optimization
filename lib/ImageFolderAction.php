<?php
class ImageFolderAction {

    public $fileGlobInput = '/c*.jpeg';
    
    public $keepPositioningImage = false;

    protected $inputPath = '';

    protected $outputPath = '';

    public function __construct(string $inputPath, string $outputPath) {
        $this->inputPath = $inputPath;
        $this->outputPath = $outputPath;
    }

    public function run() {        
        $imageFileList = glob($this->inputPath . $this->fileGlobInput);
        foreach ($imageFileList as $imageFile) {
            $outputFile = $this->outputPath . DIRECTORY_SEPARATOR . basename($imageFile);

            $imageAction = new ImageAction($imageFile, $outputFile);
            $imageAction->run();
        }
    }
}