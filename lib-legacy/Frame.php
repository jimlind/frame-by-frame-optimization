<?php
class Frame {
    // This shouldn't really change, seems to be good
    protected static $xPosition = 550;

    public static function convert(string $imageFolder, string $fileGlobInput, string $outputPath, bool $keepPositioningImage) {
        $imageOutputFolder = $outputPath . '/' . basename($imageFolder);
        FileSystemHelper::make($imageOutputFolder);

        $imageFileList = glob($imageFolder . $fileGlobInput);
        foreach ($imageFileList as $imageFile) {
            self::fixDistort($imageFile);

            $tmpFile = $keepPositioningImage ? $imageOutputFolder . '/_' . basename($imageFile) : '';
            $positioningImage = self::writePositioningImage($tmpFile);

            $yPosition = PositionFourCorners::getY($positioningImage);
            if ($yPosition) {
                $croppedFile = $imageOutputFolder . '/' . basename($imageFile);
                self::writeCroppedImage($yPosition, $croppedFile);
            } else {
                print_r('NOTHING WRITTEN!' . PHP_EOL);
            }
        }
    }

    public static function look(string $imageFolder, string $fileGlobInput) {
        $diffData = [
        	'sproket' => [],
            'top' => [],
            'bottom' => [],
        ];

        $imageFileList = glob($imageFolder . $fileGlobInput);
        foreach ($imageFileList as $imageFile) {
            if (rand(0, 20) === 0) {
                self::fixDistort($imageFile);
                $positioningImage = self::writePositioningImage();
                $positionData = PositionFourCorners::gatherPositionData($positioningImage);
                if (empty($positionData)) {
                    continue;
                }

				$diffData['sproket'][] = $positionData['sproketDiff'];
                $diffData['top'][] = $positionData['topDiff'];
                $diffData['bottom'][] = $positionData['bottomDiff'];
            }
        }

        return $diffData;
    }

    public static function borderFinder(string $imageFolder, string $fileGlobInput, string $outputPath) {
        $imageOutputFolder = $outputPath . '/' . basename($imageFolder);

        $imageFileList = glob($imageFolder . $fileGlobInput);
        foreach ($imageFileList as $imageFile) {
            print($imageFile . PHP_EOL);
            self::fixDistort($imageFile);
            $positioningImage = self::writePositioningImage();
            $data = PositionFourCorners::getBorderData($positioningImage);
            
            $croppedFile = $imageOutputFolder . '/' . basename($imageFile);
            self::writeCroppedImage($data['top'], $croppedFile);
        }
    }
}