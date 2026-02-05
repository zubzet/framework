<?php

use ZubZet\Framework\Testing\Coverage\Collector;
    /**
     * Entrance for the framework. This file should be copied in the root of the project. A .htaccess file should be created which redirects all non-static requests to this file.
     */

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    chdir(realpath(__DIR__));

    $source = getenv('COMPOSER_VENDOR_DIR') ?: "./";
    require_once "$source/autoload.php";

    use SebastianBergmann\CodeCoverage\Filter;
    use SebastianBergmann\CodeCoverage\Driver\Selector;
    use SebastianBergmann\CodeCoverage\CodeCoverage;
    use SebastianBergmann\CodeCoverage\Report\Clover;

    $filter = new Filter;
    $filter->includeDirectory('.');
    $filter->excludeDirectory("vendor");
    $filter->excludeDirectory("**/cypress/**");

    $coverage = new CodeCoverage(
        (new Selector)->forLineCoverage($filter),
        $filter
    );

    $coverage->start("e2e");

    register_shutdown_function(function() use ($coverage) {
        $coverage->stop();
        $writer = new Clover;
        $writer->process($coverage, ".coverage/".uniqid().".xml");
    });

    //z_framework init
    $z_framework = new z_framework();
    $z_framework->execute();

?>