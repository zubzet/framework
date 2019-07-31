<?php

/**
 * Installer script. Call it directly by opening the url to the file path in a web browser.
 */

set_time_limit(-1);

$msg = "";
$log = "";
$mysqli = null;

$installPath = realpath("./../");

/**
 * Helper for creating directories and logging
 */
function createDirectory($dirname)
{
    global $log;
    if (!file_exists($dirname)) {
        mkdir($dirname);
        $log .= "Created directory: $dirname<br>";
    } else {
        $log .= "Directory $dirname already exists<br>";
    }
}

if (isset($_POST["db-host"])) {

    if(strlen($_POST["admin-password"]) < 3) $msg = "The password has to be at least three characters long.";

    if (empty($_POST["db-host"]) || empty($_POST["db-database"]) || empty($_POST["host"]) || empty($_POST["root"]) || empty($_POST["admin-email"]) || empty($_POST["admin-password"]) || empty($_POST["page-name"])) {
        $msg = "Please fill in all fields";
    }

    if ($msg == "") {
        $pageName = $_POST["page-name"];
        $dbHost = $_POST["db-host"];
        $dbUser = $_POST["db-user"];
        $dbPassword = $_POST["db-password"];
        $dbDatabase = $_POST["db-database"];

        $host = $_POST["host"];
        $root = $_POST["root"] . "/";

        $adminEmail = $_POST["admin-email"];
        $adminPassword = $_POST["admin-password"];

        try {
            $mysqli = new mysqli($dbHost, $dbUser, $dbPassword);
            if ($mysqli->connect_errno) $msg = "DB Connection Error: " . $mysqli->connect_error;
        } catch (Exception $e) {
            $msg = "DB Connection error!";
        }
    }

    //No error to now
    if ($msg == "") {
        //Create database
        $log .= "Creating database...<br>";
        $timeBefore = microtime(true);

        $mysqli->query("DROP DATABASE IF EXISTS $dbDatabase");
        $mysqli->query("CREATE DATABASE IF NOT EXISTS $dbDatabase");

        $mysqli->select_db($dbDatabase);

        // Temporary variable, used to store current query
        $templine = '';
        // Read in entire file
        $lines = file("install.sql");
        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;

            // Add this line to the current segment
            $templine .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                $mysqli->query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />');
                // Reset temp variable to empty
                $templine = '';
            }
        }

        //Import default structure
        $sql = file_get_contents("install.sql");
        $erg = $mysqli->multi_query($sql);

        $log .= "Database created in ";
        $log .= (microtime(true) - $timeBefore) . " seconds<br>";

        $mysqli->close();
        $mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);

        //Create default values
        $sql = "INSERT INTO `z_language` (`name`, `nativeName`, `value`) VALUES ('English', 'English', 'EN');";

        //sleep(1);
        do {
            $mysqli->query($sql);
            if ($mysqli->errno) {
                $msg = $mysqli->error;
                //sleep(1);
            }
        } while ($mysqli->errno);
        $languageId = $mysqli->insert_id;

        //sleep(1);

        //Create admin group
        $mysqli->query("INSERT INTO `z_role` (`name`) VALUES ('Admin');");
        if ($mysqli->errno) {
            $msg .= $mysqli->error . "<br>";
        }
        $adminRoleId = $mysqli->insert_id;

        $mysqli->query("INSERT INTO `z_role_permission` (`name`, `role`) VALUES ('*.*', $adminRoleId);");
        if ($mysqli->errno) {
            $msg .= $mysqli->error . "<br>";
        }

        //Create admin user
        require_once "z_libs/passwordHandler.php";
        $query = "INSERT INTO `z_user`(`email`, `languageId`) VALUES (?,?)";
        $password = passwordHandler::createPassword($adminPassword);
        $passwordHash = $password["hash"];
        $passwordSalt = $password["salt"];
        $mysqli->query("INSERT INTO `z_user`(`email`, `password`, `salt`, `languageId`) VALUES ('$adminEmail', '$passwordHash', '$passwordSalt', $languageId)");
        if ($mysqli->errno) {
            $msg .= $mysqli->error . "<br>";
        }
        $adminUserId = $mysqli->insert_id;

        $mysqli->query("INSERT INTO `z_user_role` (`role`, `user`) VALUES ($adminRoleId, $adminUserId)");
        if ($mysqli->errno) {
            $msg .= $mysqli->error . "<br>";
        }

        $configText = "dbhost = $dbHost\n"
            . "dbusername = $dbUser\n"
            . "dbpassword = $dbPassword\n"
            . "dbname = $dbDatabase\n"
            . "rootDirectory = $root\n"
            . "host = $host\n"
            . "dedicated_mail = \n"
            . "showErrors = 2\n"
            . "defaultIndex = IndexController\n"
            . "uploadFolder = uploads/\n"
            . "loginTimeoutSeconds = 43200\n"
            . "maxLoginTriesTimespan = 3 minutes\n"
            . "maxLoginTriesPerTimespan = 5\n"
            . "forgotPasswordTimeSpan = 60 minutes\n"
            . "sitemapPublicDefault = false\n"
            . "assetVersion=1\n"
            . "pageName=$pageName";

        chdir("../");
        require("z_framework/updater.php");
        file_put_contents("z_config/z_settings.ini", $configText);
        chdir("./z_framework");

        //Hard-Verify admin accounts mail
        $mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
        $mysqli->query("UPDATE `z_user` SET verified = '2000-01-01' WHERE id = $adminUserId");
        $mysqli->close();

        //Composer shit
        $log .= "Downloading composer installer...<br>";
        copy('https://getcomposer.org/installer', './../composer-setup.php');
        $log .= "Executing composer installer...<br>";
        exec('cd ./../ && php composer-setup.php');
        $log .= "Deleting composer installer...<br>";
        unlink("./../composer-setup.php");
        $log .= "Getting html2pdf with composer<br>";
        exec('cd ./../ && php composer.phar require spipu/html2pdf');
        $log .= "Finished!<br>";

        echo $log;
    }
}

/**
 * Helper for inserting values in inputs without making errors / warning
 */
function value($name)
{
    if (isset($_POST[$name])) {
        echo $_POST[$name];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Z-Installer</title>
    <style>
        body {
            font-family: sans-serif;
        }

        label {
            display: inline-block;
            width: 200px;
        }

        .input-group {
            margin-bottom: 3px;
        }

        .msg {
            padding: 3px;
            border: 1px solid black;
        }

        #url-preview {
            font-family: monospace;
            white-space: pre;
        }

        .lds-roller {
            display: inline-block;
            position: relative;
            width: 64px;
            height: 64px;
        }

        .lds-roller div {
            animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            transform-origin: 32px 32px;
        }

        .lds-roller div:after {
            content: " ";
            display: block;
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #fff;
            margin: -3px 0 0 -3px;
        }

        .lds-roller div:nth-child(1) {
            animation-delay: -0.036s;
        }

        .lds-roller div:nth-child(1):after {
            top: 50px;
            left: 50px;
        }

        .lds-roller div:nth-child(2) {
            animation-delay: -0.072s;
        }

        .lds-roller div:nth-child(2):after {
            top: 54px;
            left: 45px;
        }

        .lds-roller div:nth-child(3) {
            animation-delay: -0.108s;
        }

        .lds-roller div:nth-child(3):after {
            top: 57px;
            left: 39px;
        }

        .lds-roller div:nth-child(4) {
            animation-delay: -0.144s;
        }

        .lds-roller div:nth-child(4):after {
            top: 58px;
            left: 32px;
        }

        .lds-roller div:nth-child(5) {
            animation-delay: -0.18s;
        }

        .lds-roller div:nth-child(5):after {
            top: 57px;
            left: 25px;
        }

        .lds-roller div:nth-child(6) {
            animation-delay: -0.216s;
        }

        .lds-roller div:nth-child(6):after {
            top: 54px;
            left: 19px;
        }

        .lds-roller div:nth-child(7) {
            animation-delay: -0.252s;
        }

        .lds-roller div:nth-child(7):after {
            top: 50px;
            left: 14px;
        }

        .lds-roller div:nth-child(8) {
            animation-delay: -0.288s;
        }

        .lds-roller div:nth-child(8):after {
            top: 45px;
            left: 10px;
        }

        @keyframes lds-roller {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <h1>Z-Installer</h1>
    <?php
    if ($msg != "") {
        ?><div class="msg"><?php echo $msg; ?></div><?php
                                                        }
                                                        ?>

    <form action="" method="post">
        <h2>Pagename</h2>
        <p>The page name will be visible to the user as the sender of mails or in the title of error pages.</p>
        <div class="input-group">
            <label for="page-name">Name</label>
            <input id="page-name" name="page-name" type="text" placeholder="Page123" value="<?php value("page-name"); ?>">
        </div>
        <h2>Database</h2>
        <div class="input-group">
            <label for="db-host">DB Host</label>
            <input id="db-host" name="db-host" type="text" placeholder="127.0.0.1" value="<?php value("db-host"); ?>">
        </div>
        <div class="input-group">
            <label for="db-user">DB User</label>
            <input id="db-user" name="db-user" type="text" autocomplete="new-password" placeholder="user" value="<?php value("db-user"); ?>">
        </div>
        <div class="input-group">
            <label for="db-password">DB User Password</label>
            <input id="db-password" name="db-password" type="password" autocomplete="new-password" placeholder="*******" value="<?php value("db-password"); ?>">
        </div>
        <div class="input-group">
            <label for="db-database">DB Database</label>
            <input id="db-database" name="db-database" type="text" placeholder="YourWebsite" value="<?php value("db-database"); ?>">
        </div>
        <h2>Host</h2>
        <div class="input-group">
            <label for="host">Host</label>
            <input id="host" name="host" type="text" placeholder="www.yourwebsite.com" value="<?php value("host"); ?>">
        </div>
        <div class="input-group">
            <label for="root">Root Directory</label>
            <input id="root" name="root" type="text" placeholder="yourwebsite" value="<?php value("root"); ?>">
        </div>
        <p>URL Preview: <span id="url-preview"></span></p>
        <h2>Admin Account</h2>
        <div class="input-group">
            <label for="admin-email">Email</label>
            <input id="admin-email" name="admin-email" type="email" placeholder="admin@yourwebsite.com" value="<?php value("admin-email"); ?>">
        </div>
        <div class="input-group">
            <label for="admin-password">Password</label>
            <input id="admin-password" name="admin-password" type="password" placeholder="Not 123456" value="<?php value("admin-password"); ?>">
        </div>
        <p>The project will be installed into this directory: <span style="font-family: monospace; background-color: #FFFFCC;"><?php echo $installPath ?></span></p>
        <button id="btn-install">Install</button>

        <div id="loader" style="width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; background: rgba(0, 0, 0, 0.6); display: none; text-align: center;">
            <div style="margin: auto; margin-top: 30vh; color: white">Please wait a moment while the framework is installing!</div>
            <div style="margin: auto;" class="lds-roller">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </form>
    <script>
        document.getElementById("host").addEventListener("keyup", updateURLPreview);
        document.getElementById("root").addEventListener("keyup", updateURLPreview);

        function updateURLPreview() {
            var host = document.getElementById("host").value;
            var root = document.getElementById("root").value;
            document.getElementById("url-preview").innerHTML = host + "/" + root + "/";
        }

        updateURLPreview();

        function load() {
            document.getElementById("loader").style.display = "initial";
        }
        document.getElementById("btn-install").addEventListener("click", function() {
            load();
        });
    </script>
</body>

</html>