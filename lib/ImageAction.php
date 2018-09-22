<?php
class ImageAction {

    protected $filePath = '';

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function run() {
        print('.');
    }
}