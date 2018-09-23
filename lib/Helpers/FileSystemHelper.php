<?php
namespace Helpers;

class FileSystemHelper {
    /**
     * Make Directory
     */
    public static function md(string $path) : bool {
        if (is_dir($path) === false) {
            return mkdir($path, 0777, true);
        }

        return true;
    }
}