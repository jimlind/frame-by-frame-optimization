<?php
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

// Find all neccessary image folders and loop over them
foreach (glob($capPathGlobInput) as $imageFolder) {
    Frame::convert($imageFolder, $outputPath);
}
// TODO: When I know this is working perfectly renable video conversion
// Video::convert($outputPath);
exit();
