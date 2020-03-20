<?php
    /**
     * Entrance for the framework. This file should be copied in the root of the project. A .htaccess file should be created which redirects all non-static requests to this file.
     */

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once "z_framework/main.php";

    //z_framework init
    $z_framework = new z_framework();
    $z_framework->execute();

?>