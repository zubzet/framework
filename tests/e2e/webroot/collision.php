<?php

    chdir(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

    $source = getenv('COMPOSER_VENDOR_DIR') ?: "./";
    require_once "$source/autoload.php";

    function model() {}

    new z_framework();
?>