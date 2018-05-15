<?php
class Dir {
    public static function make(string $path) {
        if (is_dir($path) === false) {
            mkdir($path);
        }
    }
}