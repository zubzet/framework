<?php
    function import(string $file) {
        $cmd = <<<END
            docker exec -i database \
            mysql -uapp -papp_password \
            app < $file;
        END;
        echo shell_exec($cmd);
    }

    function drop() {
        $cmd = <<<END
            docker exec -i database \
            mysql -uroot -proot_password \
            -e 'DROP DATABASE IF EXISTS app; CREATE DATABASE app;';
        END;
        shell_exec($cmd);
    }

    function getFiles(string $path): array {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $file = $file->getPathname();
        //    if(str_contains($file, "support")) continue;
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