<?php
// IGNORE NOTICES
error_reporting(E_ALL & ~E_NOTICE);

$frameDir = './framesY/';
mdir($frameDir);

$input = './cap/1*/';
foreach (glob($input) as $fileDir) {
    convertImages($fileDir, $frameDir);
}
createVideo($frameDir);
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

function createVideo($frameDir) {
    $cleanGlob = $frameDir . '1*/' . 'c*.jpeg';
    $stabilitySectionOutput = $frameDir . 'stability.mp4';
    $stabilitySectionCommand = [
        'ffmpeg',
        '-pattern_type glob',
        '-i "'.$cleanGlob.'"',
        '-vf crop=iw:80:0:0',
        '-c:v libx264',
        '-preset ultrafast',
        '-y '.$stabilitySectionOutput,
    ];
    print_r(implode(' ', $stabilitySectionCommand));
    shell_exec(implode(' ', $stabilitySectionCommand));

    $stabilityResultOutput = $frameDir . 'out.trf';
    $stabilityDetectCommand = [
        'ffmpeg',
        '-i '.$stabilitySectionOutput,
        '-vf vidstabdetect=shakiness=10:stepsize=1:mincontrast=0:result="'.$stabilityResultOutput.'"',
        '-f null -',
    ];
    print_r(implode(' ', $stabilityDetectCommand));
    shell_exec(implode(' ', $stabilityDetectCommand));
    
    $finalVideoOutput = $frameDir . 'final.mp4';
    $stabilityTransformCommand = [
        'ffmpeg',
        '-r 16',
        '-pattern_type glob',
        '-i "'.$cleanGlob.'"',
        '-filter:v "vidstabtransform=smoothing=100:optzoom=0:maxangle=0:input=\''.$stabilityResultOutput.'\', crop=1465:1070:30:10"',
        '-c:v libx264',
        '-preset slow',
        '-crf 22',
        '-y '.$finalVideoOutput,
    ];
    print_r(implode(' ', $stabilityTransformCommand));
    shell_exec(implode(' ', $stabilityTransformCommand));

    //'-filter:v "vidstabtransform=maxangle=0:input=\''.$transformOutput.'\', crop=1465:1070:0:70"',
    //print_r(implode(' ', $stabilityTransformCommand));
    //shell_exec(implode(' ', $stabilityTransformCommand));
    //shell_exec('rm -rf ' . $frameDir);
}

// 
// $videoOutput = $output.'/out.mp4';
// $videoEncodeCommand = [
// 	'ffmpeg',
// 	'-r 16',
// 	'-pattern_type glob -i "'.$output.'/*.jpeg"',
// 	'-c:v libx264 '. $videoOutput,
// ];
// shell_exec(implode(' ', $videoEncodeCommand));
// 
// ffmpeg -pattern_type glob -i "./41-clean/*.jpeg" -filter:v "vidstabtransform=maxangle=0:input='transforms.trf', crop=1465:1070:0:70" -y clip-stabilized.mov

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