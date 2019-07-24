<?php 
    /**
     * The updater script
     */

    $log = "Updating...<br>";

    $newVersion = file_get_contents("z_framework/cv.txt");
    $currentVersion = file_get_contents(".z_framework");
    $log .= "Current version: $currentVersion<br>";
    $log .= "New version: $newVersion<br>";

    if ($newVersion == $currentVersion) die ("No update needed!");

    $log .= "Copy files...<br>";
    copy("z_framework/install/index.php", "index.php");
    copy("z_framework/install/.htaccess", ".htaccess");
    copy("z_framework/default/assets/Z.js", "assets/js/Z.js");
    copy("z_framework/default/assets/css/bootstrap.min.css", "assets/css/bootstrap.min.css");
    copy("z_framework/default/assets/css/loadCircle.css", "assets/css/loadCircle.css");
    copy("z_framework/default/assets/css/font-awesome.css", "assets/css/font-awesome.css");
    $log .= "All files copied!";

    file_put_contents(".z_framework", $newVersion);

?>