<?php
class FileSystemHelper {
    /**
     * Make Directory
     */
    public static function md(string $path) : string {
        if (is_dir($path) === false) {
            mkdir($path);
        }

        return realpath($path);
    }
}