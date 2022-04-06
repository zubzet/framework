<?php 
    /**
     * The updater script
     */

    /**
     * Helper for creating directories
     */
    function createDirectoryUpdater($dirname) {
        global $log;
        if (!file_exists($dirname)) {
            mkdir($dirname);
        }
    }

    function getDirContents($dir, &$results = []){

        if($results == []) $results = [
            "files" => [],
            "folders" => []
        ];

        $files = scandir($dir);
    
        foreach($files as $key => $value){
            $path = $dir."/".$value;
            if(!is_dir($path)) {
                $parts = explode("/", $path);
                $results["files"][] = $path;
            } else if($value != "." && $value != "..") {
                getDirContents($path, $results);
                $results["folders"][] = $path;
            }
        }
    
        return $results;
    }

    $log = "Updating...<br>";

    $newVersion = file_get_contents("z_framework/cv.txt");
    if(!file_exists(".z_framework")) file_put_contents(".z_framework", 0);
    $currentVersion = file_get_contents(".z_framework");
    $log .= "Current version: $currentVersion<br>";
    $log .= "New version: $newVersion<br>";

    if ($newVersion == $currentVersion) {
        if(isset($argv[1]) && $argv[1] == "ignore-version") {
            $log .= "No update needed, but this has been ignored.<br>";
        } else {
            die ("No update needed!");
        }
    }

    $log .= "Creating directories...<br>";
    createDirectoryUpdater("z_config");
    createDirectoryUpdater("z_models");
    createDirectoryUpdater("z_views");
    createDirectoryUpdater("z_controllers");
    createDirectoryUpdater("uploads");
    createDirectoryUpdater("assets");

    $copy_tasks = getDirContents("z_framework/default/assets");
    $copy_tasks["folders"] = array_reverse($copy_tasks["folders"]);
    foreach($copy_tasks["folders"] as $folder) {
        $folder = str_replace("z_framework/default/", "", $folder);
        createDirectoryUpdater($folder);
    }

    $log .= "All directories created...<br>";

    $log .= "Copy files...<br>";
    copy("z_framework/install/index.php", "index.php");
    copy("z_framework/install/.htaccess", ".htaccess");

    foreach($copy_tasks["files"] as $file) {
        $file_copy = str_replace("z_framework/default/", "", $file);
        $parts = explode("/", $file);
        $file_name = $parts[count($parts) - 1];
        if($file_name != "bootstrap.min.css" || ($file_name == "bootstrap.min.css" && !file_exists("assets/css/bootstrap.min.css"))) {
            $log .= $file_copy;
            copy($file, $file_copy);
        }
    }

    $log .= "All files copied!<br>";

    if (!file_exists(".gitignore")) {
        copy("z_framework/default/gitignore", ".gitignore");
    }

    $cfg = parse_ini_file("z_config/z_settings.ini");
    $mysqli = new mysqli($cfg["dbhost"], $cfg["dbusername"], $cfg["dbpassword"], $cfg["dbname"]);
    if ($mysqli->errno) die ($mysqli->error);

    $log .= "Updating database...<br>";
    //It errors when coloumn already exists. Can be ignored
    $mysqli->query("ALTER TABLE z_user ADD verified TIMESTAMP NULL");
    $mysqli->query("CREATE TABLE `z_email_verify` ( `id` INT NOT NULL AUTO_INCREMENT , `token` VARCHAR(255) NOT NULL , `user` INT NOT NULL , `end` DATETIME NOT NULL , `active` INT NOT NULL DEFAULT '1' , `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = InnoDB;");

    $mysqli->close();
    $log .= "Database should be up to date now!<br>";

    if (!isset($cfg["pageName"])) {
        file_put_contents("z_config/z_settings.ini", "\npageName = Your Website", FILE_APPEND);
    }
    if (!isset($cfg["mail_smtp"])) {
        file_put_contents("z_config/z_settings.ini", "\nmail_smtp = ", FILE_APPEND);
    }
    if (!isset($cfg["mail_user"])) {
        file_put_contents("z_config/z_settings.ini", "\nmail_user = ", FILE_APPEND);
    }
    if (!isset($cfg["mail_password"])) {
        file_put_contents("z_config/z_settings.ini", "\nmail_password = ", FILE_APPEND);
    }
    if (!isset($cfg["registerRoleId"])) {
        file_put_contents("z_config/z_settings.ini", "\nregisterRoleId = -1", FILE_APPEND);
    }
    if (!isset($cfg["anonymous_language"])) {
        file_put_contents("z_config/z_settings.ini", "\nanonymous_language = en", FILE_APPEND);
    }
    if (!isset($cfg["anonymous_available_languages"])) {
        file_put_contents("z_config/z_settings.ini", "\nanonymous_available_languages = en, de", FILE_APPEND);
    }
    if (!isset($cfg["lite_mode"])) {
        file_put_contents("z_config/z_settings.ini", "\nlite_mode = Off", FILE_APPEND);
    }

    //Composer shit
    $log .= "Downloading composer installer...<br>";
    copy('https://getcomposer.org/installer', './composer-setup.php');
    $log .= "Executing composer installer...<br>";
    exec('cd ./ && php composer-setup.php');
    $log .= "Deleting composer installer...<br>";
    unlink("./composer-setup.php");
    $log .= "Getting html2pdf with composer<br>";
    exec('cd ./ && php composer.phar require spipu/html2pdf');
    $log .= "Getting phpmailer with composer<br>";
    exec('cd ./ && php composer.phar require phpmailer/phpmailer');
    $log .= "Finished!<br>";

    file_put_contents(".z_framework", $newVersion);

?>