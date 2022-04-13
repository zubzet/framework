<?php
    /**
     * Entrance for the framework. This file should be copied in the root of the project. A .htaccess file should be created which redirects all non-static requests to this file.
     */

    require_once "vendor/autoload.php";

    // Framework Initialization
    $app = new Zubzet\Core();
    $app->execute();

?>