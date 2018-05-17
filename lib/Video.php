<?php
class Video {
    private static $deleteTempFiles = false;

    public static function convert(string $outputPath) {
        $frameGlob = $outputPath . '/*/' . 'c*.jpeg';
        $stabilitySectionOutput = $outputPath . '/stability.mp4';
        $stabilitySectionCommand = [
            'ffmpeg',
            '-pattern_type glob',
            '-i "'.$frameGlob.'"',
            '-vf crop=iw:80:0:0',
            '-c:v libx264',
            '-preset ultrafast',
            '-y '.$stabilitySectionOutput,
        ];
        shell_exec(implode(' ', $stabilitySectionCommand));
    
        $stabilityResultOutput = $outputPath . '/out.trf';
        $stabilityDetectCommand = [
            'ffmpeg',
            '-i '.$stabilitySectionOutput,
            '-vf vidstabdetect=shakiness=10:stepsize=1:mincontrast=0:result="'.$stabilityResultOutput.'"',
            '-f null -',
        ];
        shell_exec(implode(' ', $stabilityDetectCommand));
        
        $finalVideoOutput = $outputPath . '/final.mp4';
        $stabilityTransformCommand = [
            'ffmpeg',
            '-r 16',
            '-pattern_type glob',
            '-i "'.$frameGlob.'"',
            '-filter:v "vidstabtransform=smoothing=100:optzoom=0:maxangle=0:input=\''.$stabilityResultOutput.'\', crop=1465:1070:30:10"',
            '-c:v libx264',
            '-preset slow',
            '-crf 22',
            '-y '.$finalVideoOutput,
        ];
        shell_exec(implode(' ', $stabilityTransformCommand));

        if (self::$deleteTempFiles) {
            unlink($stabilitySectionOutput);
            unlink($stabilityResultOutput);
        }
    }
}