<?php

    // -----------------------------
    // Do not change this file as it is the entrypoint for web requests.
    // -----------------------------

    $calledFromWebroot = true;

    chdir(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

    // Try multiple locations for the entry scripts
    $entryScripts = [
        "index.php",
        "zubzet.php",
        "zubzet",
    ];

    $entryScriptFound = false;

    foreach($entryScripts as $entryScript) {
        if(!file_exists($entryScript)) continue;

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