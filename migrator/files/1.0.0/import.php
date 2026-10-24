<?php
    // Config
    $DB_HOST = 'database';
    $DB_NAME = 'app';
    $ROOT_USER = 'root';
    $ROOT_PASS = 'root_password';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    function db(): mysqli {
        global $DB_HOST, $DB_NAME, $ROOT_USER, $ROOT_PASS;
        return new mysqli($DB_HOST, $ROOT_USER, $ROOT_PASS, $DB_NAME);
    }

    function import(string $file) {
        $conn = db();
        $sql = file_get_contents($file);
        if ($sql) {
            $conn->multi_query($sql);
            while ($conn->more_results() && $conn->next_result()) {}
        }
        $conn->close();
    }

    function drop() {
        global $DB_NAME;
        $conn = db();
        $sql = "DROP DATABASE IF EXISTS `$DB_NAME`; CREATE DATABASE `$DB_NAME`";
        $conn->multi_query($sql);
        while ($conn->more_results() && $conn->next_result()) {}
        $conn->close();
    }

    function getFiles(string $path): array {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $file = $file->getPathname();
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if("sql" !== $extension) continue;
            $files[] = $file;
        }
        sort($files);
        return $files;
    }

    function importFolder(string $path) {
        $buffer = "";
        foreach(getFiles($path) as $file) {
            echo "Importing $file\n";
            $buffer .= "\n\n" . file_get_contents($file);
        }
        $file = tempnam(sys_get_temp_dir(), "migration_import");
        file_put_contents($file, $buffer);
        echo "Buffer saved at: $file\n";
        import($file);
    }

    chdir(__DIR__);

    $importTime = microtime(true);

    // Reset
    drop();

    // Import all sql files
    importFolder(".");

    // Time difference
    $finishTime = microtime(true);
    echo "Time taken: ".number_format(($finishTime - $importTime), 3)."s\n";

?>
