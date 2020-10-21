<?php
    class CommandLine_Core {
        private $booter;
        public function __construct(&$booter) {
            global $argv;
            $this->booter = $booter;

            require_once __DIR__."/Console.php";

            $cmd = strtolower($argv[1] ?? "");
            $cmd = str_replace("-", "_", $cmd);

            $commands = [
                "update" => [
                    "file" => "/../Update/UpdateManager.php",
                    "class" => "UpdateManager"
                ],
                "migrate_cli" => [
                    "file" => "/../Update/UpdateManager.php",
                    "class" => "UpdateManager",
                    "method" => "migrate_cli"
                ],
                "build" => [
                    "file" => "/../Builders/BuilderManager.php",
                    "class" => "BuilderManager"
                ]
            ];

            require_once __DIR__."/ConsoleContext.php";
            $context = new ConsoleContext();

            if(isset($commands[$cmd])) {
                require_once __DIR__.$commands[$cmd]["file"];
                $update = new $commands[$cmd]["class"]($this->booter);
                return $update->{$commands[$cmd]["method"] ?? "run"}(
                    $this->booter->req,
                    $this->booter->res,
                    $context
                );
            }
            Console::error("Command '".($argv[1] ?? "")."' not found. Try php zubzet help");
        }

    }
?>