<?php
class FileSystemHelper {
    public static function make(string $path) : string {
        if (is_dir($path) === false) {
            mkdir($path);
        }

        return realpath($path);
    }
}