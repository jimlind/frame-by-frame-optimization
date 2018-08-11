<?php
class MathHelper {
    public static function avg($list) {
        if (count($list) === 0) {
            return 0;
        }

        return array_sum($list) / count($list);
    }

    public static function in($value, $min, $max){
        // print_r([$value, $min, $max, $min < $value && $value < $max]);
        return ($min < $value && $value < $max);
    }
}