<?php 

const createDirectories = [
    "webroot",
    "app",
    "app/Controllers",
    "app/Models",
    "app/Views",
    "app/Database",
    "app/Routes"
];

const moveDirectories = [
    "z_controllers" => "app/Controllers",
    "z_models"      => "app/Models",
    "z_views"       => "app/Views",
    "z_database"    => "app/Database",
    "routes"        => "app/Routes",
];

const moveWebrootExceptions = [
    "app",
    "webroot",
    "z_framework", "_framework",
    ".vscode",
    "node_modules",
    "package.json", "package-lock.json",
    "composer.json", "composer.lock", "composer.local.json",
    ".dockerignore", "Dockerfile",
    ".htaccess",
    ".z_framework",
    "index.php",
    ".drone.yml",
    ".gitignore",
    ".git",
    "z_config",
    "vendor",
    "Validator", "helper",
    "packaging",
    "tests",
    "libs",
    "TODO",
    "sql.log",
    "README.md",
    "LICENSE",
    "src",
    "examples"
];

chdir("employee-search");

foreach (createDirectories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// use method for moving recursive
function moveRecursive(string $from, string $to): void {
    if (!is_dir($from)) return;
    if (!is_dir($to)) {
        mkdir($to, 0777, true);
    }

    $items = scandir($from);
    foreach ($items as $item) {
        if ($item === "." || $item === "..") continue;

        $src = $from . DIRECTORY_SEPARATOR . $item;
        $dst = $to   . DIRECTORY_SEPARATOR . $item;

        if (is_dir($src)) {
            moveRecursive($src, $dst);
            @rmdir($src);
        } else {
            rename($src, $dst);
        }
    }
}

foreach (moveDirectories as $from => $to) {
    if (is_dir($from)) {
        moveRecursive($from, $to);
        @rmdir($from);
    }
}

$rootItems = scandir(".");
foreach ($rootItems as $item) {
    if ($item === "." || $item === "..") continue;
    if (in_array($item, moveWebrootExceptions)) continue;

    $src = $item;
    $dst = "webroot" . DIRECTORY_SEPARATOR . $item;

    rename($src, $dst);
}



// Updates package.json
$content = file_get_contents("package.json");

if ($content === false) {
    die("Could not read package.json\n");
}

// Check if "z_database/import.php" exists
if (strpos($content, "z_database/import.php") !== false) {
    // Replace
    $content = str_replace(
        "z_database/import.php",
        "app/Database/import.php",
        $content
    );

    file_put_contents("package.json", $content);
}



$content = file_get_contents("index.php");
if( $content === false) {
    die("Could not read index.php\n");
}

if (preg_match('/\$z_framework\s*->\s*execute\s*\(\s*\)\s*;/', $content)) {
    $newContent = preg_replace(
        '/\$z_framework\s*->\s*execute\s*\(\s*\)\s*;/',
        '$z_framework->handleRequest();',
        $content
    );

    file_put_contents("index.php", $newContent);
}



// Create webroot/index.php
touch("webroot/index.php");
file_put_contents("webroot/index.php", '
<?php

    // -----------------------------
    // Do not change this file as it is the entrypoint for web requests.
    // -----------------------------

    chdir(__DIR__ . DIRECTORY_SEPARATOR . \'..\' . DIRECTORY_SEPARATOR);

    // Try multiple locations for the entry scripts
    $entryScripts = [
        "index.php",
        "zubzet.php",
        "zubzet",
    ];

    $entryScriptFound = false;

    foreach($entryScripts as $entryScript) {
        if(!file_exists($entryScript)) break;

        require_once $entryScript;
        $entryScriptFound = true;
    }

    // If no entry script is found, return a 500 error
    if(!$entryScriptFound) {
        http_response_code(500);
        echo "No entry script found.";
        exit(1);
    }

?>
');


// Remove old .htaccess if exists and create a new one in webroot
unlink(".htaccess");
touch("webroot/.htaccess");
file_put_contents("webroot/.htaccess", '
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule . index.php [L,QSA]
');


// Update the composer.json
exec("composer require zubzet/framework:dev-main");
