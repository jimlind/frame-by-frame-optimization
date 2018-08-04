<?php
class MathHelper {
    public static function avg($list) {
        return array_sum($list) / count($list);
    }
}