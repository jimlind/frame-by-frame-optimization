<?php
class BorderFinder {
    public static function getBorderCenters($brightnessList): array {
        arsort($brightnessList);
        $brightnessSlice = array_slice($brightnessList, 0, 120, true);
        ksort($brightnessSlice);

        $diffs = [];
        $previousKey = key($brightnessSlice);
        foreach ($brightnessSlice as $key => $value) {
            $diffs[] = $key - $previousKey;
            $previousKey = $key;
        }
        $borderValue = max($diffs);
        $borderIndex = array_search($borderValue, $diffs);

        $topSlice = array_slice($brightnessSlice, 10, $borderIndex - 10, true);
        $bottomSlice = array_slice($brightnessSlice, $borderIndex + 10, -10, true);

        $topBorder = MathHelper::avg(array_keys($topSlice));
        $bottomBorder = MathHelper::avg(array_keys($bottomSlice));
        
        return [
            'top' => round($topBorder),
            'bottom' => round($bottomBorder),
        ];
    }
}