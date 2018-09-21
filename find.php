<?php
// HOW TO USE
// php find.php ../RED-SANDSK/
// >>>> ../RED-SANDSK/  (this is the path that the `cap` dir is in)

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

// Find all neccessary image folders and loop over them
$allResults = [];
foreach (glob($inputPath . '/cap/*') as $imageFolder) {
    $result = Frame::look($imageFolder, '/c*.jpeg');
    if (!empty($result['top'])) {
        $allResults = array_merge_recursive($allResults, $result);
    }
}

print_r('Sproket: ' . round(MathHelper::avg($allResults['sproket'])) . PHP_EOL);
print_r('Top: ' . round(MathHelper::avg($allResults['top'])) . PHP_EOL);
print_r('Bottom: ' . round(MathHelper::avg($allResults['bottom'])) . PHP_EOL);

exit();
