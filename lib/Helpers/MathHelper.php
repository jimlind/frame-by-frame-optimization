<?php
namespace Helpers;

class MathHelper {
    /**
     * Average value in an array
     */
    public static function average(array $list, bool $round = false) {
        if (count($list) === 0) {
            return 0;
        }
        $value = array_sum($list) / count($list);

        return $round ? round($value) : $value;
    }

    /**
     * Median value in an array
     */
    public static function median(array $list, bool $round = false) {
        if (empty($list)) {
            return 0;
        }

        $c = count($list);
        $m = floor($c / 2);
        sort($list, SORT_NUMERIC);
        $r = $list[$m];
        if ($c % 2 == 0) {
          $r = ($r + $list[$m - 1]) / 2;
        }

        return $round ? round($r) : $r;
    }

    /**
     * Is value between two other values
     */
    public static function between($value, $min, $max){
        return ($min < $value && $value < $max);
    }
}