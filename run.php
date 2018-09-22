<?php
include_once './lib/Autoloader.php';

// HOW TO USE
// php run.php ../RED-SANDSK/ 'output-test' '003/c009*' 1
// >>>> ../RED-SANDSK/  (this is the path that the `cap` dir is in)
// >>>> 'output-test'   (this is the output dir we are creating next to the `cap`; defaults to 'output')
// >>>> '003/c009*'     (this is the filtering we are putting on input images; defaults to '*/c*.jpeg')
// >>>> 1               (this is to keep the positioning image for debugging purposes)

// Check CLI argument or give user a prompt
$inputArg = $argv[1] ?? '';
if (empty($inputArg)) {
    print('Enter fully qualified path that has the cap directory: ');
    $handle = fopen('php://stdin', 'r');
    $inputArg = fgets($handle);
}

// Validate input directory
$inputPath = realpath($inputArg);
$capturePath = $inputPath . '/cap';
if (!is_dir($capturePath)) {
    throw new Exception('NEED VALID INPUT DIRECTORY');
}

// Check CLI argument for a custom output path
$outputDir = $argv[2] ?? 'output';
$outputPath = $inputPath . DIRECTORY_SEPARATOR . $outputDir;

// Check CLI argument for file limiters
$limitArgString = $argv[3] ?? '';
$limitArgList = array_filter(explode('/', $limitArgString));
$capPathGlobInput = $capturePath . DIRECTORY_SEPARATOR . ($limitArgList[0] ?? '*');
$fileGlobInput = '/' . ($limitArgList[1] ?? 'c*') . '.jpeg';

// Check CLI argument for positing image retention
$keepPositioningImage = (bool) ($argv[4] ?? false);

// Find all neccessary image folders and loop over them
foreach (glob($capPathGlobInput) as $imageInputPath) {
    $imageOutputPath = $outputPath . DIRECTORY_SEPARATOR . basename($imageInputPath);

    $imageFolderAction = new ImageFolderAction($imageInputPath, $imageOutputPath);
    $imageFolderAction->fileGlobInput = $fileGlobInput;
    $imageFolderAction->keepPositioningImage = $keepPositioningImage;
    $imageFolderAction->run();
}

// TODO: When I know this is working perfectly renable video conversion
// Video::convert($outputPath);
exit();
