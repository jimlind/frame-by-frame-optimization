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

function convertImages($fileDir, $frameDir) {
    $filePathList = glob($fileDir . 'c*.jpeg');
    foreach ($filePathList as $filePath) {
        // Setup dir to write cleaned frames
        $cleanFrameDir = $frameDir . basename($fileDir) . '/';
        mdir($cleanFrameDir);

        // Fix distorition and write to cache
        $fixDistortCommand = [
            'convert',
            $filePath,
            '-distort Barrel "0.0 -0.03 0.0 1.03"',
            '-write mpr:distort',
            '+delete',
            '\( mpr:distort',
            '-distort Perspective "560,610 560,610  560,1650 560,1650  1960,1635 1960,1650  1960,610 1960,610"',
            '+write',
            'mpc:tmp',
            '\) null:',
        ];
        shell_exec(implode(' ', $fixDistortCommand));
        
        // Write the spocket chunk to a jpeg
        $tmpFile = sys_get_temp_dir().'/film-sprocket-hole.jpg';
        //$tmpFile = $cleanFrameDir . '_' . basename($filePath);
        $cmd = [
            'convert',
            'mpc:tmp',
            '-crop 1200x1000+0+0',
            '-quality 92',
            $tmpFile
        ];
        shell_exec(implode(' ', $cmd));
        
        // Setup file to write
        $cleanFilePath = $cleanFrameDir . basename($filePath);

        // Find the middle of the sprocket hole
        $spot = findSpot($tmpFile);
        if (empty($spot)) {
            echo $cleanFilePath.' writing aborted'.PHP_EOL;
            continue;
        }
        
        // Finalize the image and write to disk
        $cropCommand = [
            'convert',
            'mpc:tmp',
            '-crop 1500x1100+'.$spot['x'].'+'.$spot['y'],
            '-sharpen 0x2',
            '-quality 100',
            $cleanFilePath,
        ];
        shell_exec(implode(' ', $cropCommand));
        
        echo $cleanFilePath.' written'.PHP_EOL;
    }
}

function findSpot($filePath) {
    $threshold = 50;

    $im = imagecreatefromjpeg($filePath);
    $v = value($im, $threshold);

    $t = $v['top'];
    $b = $v['bottom'];
    $d = $b - $t;

    if (!$t || !$b) {
        print("ERROR! No top or bottom detected." . PHP_EOL);
        return null;
    }

    while ($d < 300 && $threshold < 70) {
        $threshold += 4;
        $v = value($im, $threshold);
        $t = $v['top'];
        $b = $v['bottom'];
        $d = $b - $t;
    }

    if ($d < 300) {
        print("ERROR! Detected hole too small." . PHP_EOL);
        return null;
    }

    if ($d > 399) {
        print("ERROR! Detected hole too big." . PHP_EOL);
        return null;
    }

    $y = round(($b + $t) / 2) - 20;
    $y = refineYPosition($im, $y) - 20;

    print_r('*'.$y.' - '. (($b + $t) / 2) .' = '. ($y - (($b + $t) / 2)) . PHP_EOL);

    return ['x' => 550, 'y' => $y];
}

function value($im, $threshold = 50) {
    // Picking a starting point
    $x = 400;
    $y = 0;

    // Starting at y=0, move down 1px at a time until you hit a dark enough value
    // If avererage color is zero use ternary to make it above threshold
    while ((getAverageColor($im, $x, $y, $failure) ?: 999)  < $threshold) {
        $y += 1; // Move down
    }

    // Starting from a dark enough value, move down 1px at a time until you hit a light enough value
    $topValue = 0;
    $failure = false;
    while (!$failure) {
        $ac = getAverageColor($im, $x, $y, $failure);
        if (!$failure && $ac < $threshold) {
            $topValue = $y;
            break;
        }
        $y += 1; // Move down
    }

    // Move down 400 because most holes are ~330p and we want to be below any normal sprokets
    $y += 400;

    // Starting from below where the sprocket should end move up until you hit a light enough value
    $bottomValue = 0;
    $failure = false;
    while (!$failure) {
        $ac = getAverageColor($im, $x, $y, $failure);
        if (!$failure && $ac < $threshold) {
            $bottomValue = $y;
            break;
        }
        $y -= 1; // Move up
    }

    return [
        'top' => $topValue,
        'bottom' => $bottomValue,
    ];
}

function refineYPosition($im, $y) {
    $input = $y;
    $failure = false;
    $c = 0;
    $fixed = false;

    while (!$failure) {
        $c = getAverageColor($im, 1199, $y, $failure);
        //print_r(':a:'.$c);
        if ($c > 630) {
            break;
        }
        $fixed = true;
        $y++;
    }

    if ($fixed) {
        // skip a few
        $y += 5;
    }

    while (!$failure) {
        $c = getAverageColor($im, 1199, $y, $failure);
        //print_r(':b:'.$c);
        if ($c < 630) {
            break;
        }
        $y++;
    }

    //srsAQprint_r('xx:'.($input - $y).PHP_EOL);
    if (($y - $input) > 60) {
        return $input + 40;
    }

    if ($failure) {
        print_r('MASSIVE FAILURE!');
    }

    return $failure ? $input : $y;
}

function getAverageColor($im, $x, $y, &$failure) {
    $rgb = imagecolorat($im, $x, $y);
    if(empty($rgb)) {
        $failure = true;
        return 0;
    }

	$colors = imagecolorsforindex($im, $rgb);
	return (256 * 3) - $colors['red'] - $colors['green'] - $colors['blue'];
}

function mdir($dir) {
    if (is_dir($dir) === false) {
        mkdir($dir);
    }
}