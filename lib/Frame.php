<?php
class Frame {
    protected static $keepPositioningImage = false;
    protected static $xPosition = 550;

    public static function convert(string $imageFolder, string $outputPath) {
        $imageOutputFolder = $outputPath . '/' . basename($imageFolder);
        Dir::make($imageOutputFolder);

        $imageFileList = glob($imageFolder . '/c*.jpeg');
        foreach ($imageFileList as $imageFile) {
            self::fixDistort($imageFile);

            $tmpFile = self::$keepPositioningImage ? $imageOutputFolder . '/_' . basename($imageFile) : '';
            $positioningImage = self::writePositioningImage($tmpFile);

            $yPosition = Position::getY($positioningImage);
            if ($yPosition) {
                $croppedFile = $imageOutputFolder . '/' . basename($imageFile);
                self::writeCroppedImage($yPosition, $croppedFile);
            } else {
                print_r('NOTHING WRITTEN!' . PHP_EOL);
            }
        }
    }

    protected static function fixDistort(string $imageFile) {
        // Setup the points to use for distortion writing
        $perspectivePoints = [
            '560,610 560,610',
            '560,1650 560,1650',
            '1960,1635 1960,1650',
            '1960,610 1960,610',
        ];
        
        // Fix distorition and write to cache
        $fixDistortCommand = [
            'convert',
            $imageFile,
            '-distort Barrel "0.0 -0.03 0.0 1.03"',
            '-write mpr:distort',
            '+delete',
            '\( mpr:distort',
            '-distort Perspective "'.implode(' ', $perspectivePoints).'"',
            '+write',
            'mpc:tmp',
            '\) null:',
        ];
        shell_exec(implode(' ', $fixDistortCommand));
    }

    protected static function writePositioningImage(string $tmpFile = null) {
        // If nothing was supplied.. use a tmp file
        if (empty($tmpFile)) {
            $tmpFile = sys_get_temp_dir().'/film-sprocket-hole.jpg';
        }

        // Perform conversion
        $cmd = [
            'convert',
            'mpc:tmp',
            '-crop 1200x1000+0+0',
            '-quality 92',
            $tmpFile
        ];
        shell_exec(implode(' ', $cmd));

        return $tmpFile;
    }

    protected static function writeCroppedImage(int $yPosition, string $croppedFile) {
        // Finalize the image and write to disk
        $cropCommand = [
            'convert',
            'mpc:tmp',
            '-crop 1500x1100+' . self::$xPosition . '+' . $yPosition,
            '-sharpen 0x2',
            '-quality 100',
            $croppedFile,
        ];
        shell_exec(implode(' ', $cropCommand));
        print_r(implode(' ', $cropCommand) . PHP_EOL);

        echo $croppedFile.' written'.PHP_EOL;

    }
}