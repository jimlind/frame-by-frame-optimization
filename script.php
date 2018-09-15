<?php
// HOW TO USE
// php script.php ../RED-SANDSK/ 'output-test' '003/c009*' 1
// >>>> ../RED-SANDSK/  (this is the path that the `cap` dir is in)
// >>>> 'output-test'   (this is the output dir we are creating next to the `cap`; defaults to 'output')
// >>>> '003/c009*'     (this is the filtering we are putting on input images; defaults to '*/c*.jpeg')
// >>>> 1               (this is to keep the positioning image for debugging purposes)
// Setup Simple Class Autoloading
spl_autoload_register(function ($class_name) {
    include './lib/' . $class_name . '.php';
});

// Check CLI argument or give user a prompt
$inputArg = $argv[1] ?? '';
if (empty($inputArg)) {
    print('Enter fully qualified path that has the cap directory: ');
    $handle = fopen('php://stdin', 'r');
    $inputArg = fgets($handle);
}

// Validate input directory
$inputPath = realpath($inputArg);
if (!is_dir($inputPath . '/cap')) {
    throw new Exception('NEED VALID INPUT DIRECTORY');
}

// Check CLI argument for a custom output path
$outputArg = $argv[2] ?? 'output';
$outputPath = $inputPath . '/' . $outputArg;
$outputPath = FileSystemHelper::make($outputPath);

// Check CLI argument for file limiters
$limitArgString = $argv[3] ?? '';
$limitArgList = array_filter(explode('/', $limitArgString));
$capPathGlobInput = $inputPath . '/cap/' . ($limitArgList[0] ?? '*');
$fileGlobInput = '/' . ($limitArgList[1] ?? 'c*') . '.jpeg';

// Check CLI argument for positing image retention
$keepPositioningImage = (bool) ($argv[4] ?? false);

// Find all neccessary image folders and loop over them
foreach (glob($capPathGlobInput) as $imageFolder) {
    Frame::convert($imageFolder, $fileGlobInput, $outputPath, $keepPositioningImage);
}
// TODO: When I know this is working perfectly renable video conversion
// Video::convert($outputPath);
exit();
