<?php 
    /**
     * The updater script
     */

    /**
     * Helper for creating directories
     */
    function createDirectoryUpdater($dirname)
    {
        global $log;
        if (!file_exists($dirname)) {
            mkdir($dirname);
        }
    }

    $log = "Updating...<br>";

    $newVersion = file_get_contents("z_framework/cv.txt");
    $currentVersion = file_get_contents(".z_framework");
    $log .= "Current version: $currentVersion<br>";
    $log .= "New version: $newVersion<br>";

    if ($newVersion == $currentVersion) die ("No update needed!");

    $log .= "Creating directories...<br>";
    createDirectoryUpdater("z_config");
    createDirectoryUpdater("z_models");
    createDirectoryUpdater("z_views");
    createDirectoryUpdater("z_controllers");
    createDirectoryUpdater("uploads");
    createDirectoryUpdater("assets");
    createDirectoryUpdater("assets/js");
    createDirectoryUpdater("assets/css");
    createDirectoryUpdater("assets/css/font-awesome");
    $log .= "All directories created...<br>";

    $log .= "Copy files...<br>";
    copy("z_framework/install/index.php", "index.php");
    copy("z_framework/install/.htaccess", ".htaccess");
    copy("z_framework/default/assets/js/Z.js", "assets/js/Z.js");
    copy("z_framework/default/assets/js/jquery.min.js", "assets/js/jquery.min.js");
    copy("z_framework/default/assets/js/bootstrap.min.js", "assets/js/bootstrap.min.js");
    copy("z_framework/default/assets/css/bootstrap.min.css", "assets/css/bootstrap.min.css");
    copy("z_framework/default/assets/css/loadCircle.css", "assets/css/loadCircle.css");
    copy("z_framework/default/assets/css/font-awesome.css", "assets/css/font-awesome.css");
    copy("z_framework/default/assets/css/font-awesome/all.min.css", "assets/css/font-awesome/all.min.css");
    copy("z_framework/default/assets/css/bootstrap.min.css", "assets/css/bootstrap.min.css");
    copy("z_framework/default/assets/css/loadCircle.css", "assets/css/loadCircle.css");
    copy("z_framework/default/assets/css/font-awesome.css", "assets/css/font-awesome.css");
    $log .= "All files copied!";

    if (!file_exists(".gitignore")) {
        copy("z_framework/default/gitignore", ".gitignore");
    }

    file_put_contents(".z_framework", $newVersion);

?>