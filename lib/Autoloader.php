<?php
// Setup Simple Class Autoloading
spl_autoload_register(function($className) {
    $completeFile = implode([
        './lib/',
        str_replace('\\', DIRECTORY_SEPARATOR, $className),
        '.php'
    ]);
    if (file_exists($completeFile)) {
        include_once $completeFile;
    }
});