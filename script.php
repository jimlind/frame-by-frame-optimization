<?php
// SETUP SIMPLE CLASS AUTOLOADING
spl_autoload_register(function ($class_name) {
    include './lib/' . $class_name . '.php';
});

// Get dir from cli argument
$inputPath = $argv[1] ?? '';
if (!is_dir($inputPath . '/cap')) {
    throw new Exception('Need valid dir as argument');
}

$outputPath = $inputPath . '/output';
Dir::make($outputPath);

$capWildcardPath = $inputPath . '/cap/*';
foreach (glob($capWildcardPath) as $imageFolder) {
    Frame::convert($imageFolder, $outputPath);
}
Video::convert($outputPath);
exit();
