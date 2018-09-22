<?php
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
     * Is value between two other values
     */
    public static function between($value, $min, $max){
        return ($min < $value && $value < $max);
    }
}