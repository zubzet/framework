<?php

    namespace ZubZet\Framework\Console;

    use Symfony\Component\Console\Command\Command;
    use ZubZet\Framework\Registry\Registry;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LogEventType;

    class CommandDiscovery {

        /**
         * @internal
         *
         * Discovers convention commands from every "commands" root (userspace
         * first, then modules in order). A command file declares one global
         * class named like the file, extending the Symfony Command class.
         *
         * Instances are returned in precedence order; a class declared by an
         * earlier root shadows a same-named file in a later root.
         *
         * @return Command[]
         */
        public static function commands(): array {
            $commands = [];
            $instantiated = [];

            foreach(Registry::files("commands") as $file) {
                $class = basename($file, ".php");

                try {
                    // First loaded wins: a same-named class from an earlier root
                    // already claimed this class name.
                    if(!class_exists($class, false)) {
                        include_once $file;
                    }

                    if(!class_exists($class, false)) continue;
                    if(isset($instantiated[$class])) continue;
                    if(!is_subclass_of($class, Command::class)) continue;

                    $instantiated[$class] = true;
                    $commands[] = new $class();
                } catch(\Throwable $e) {
                    // One broken command file must not take down the whole CLI
                    logger(Logger::ZUBZET)->warning(LogEventType::WARNING, [
                        "message" => "The command file '$file' was skipped: {$e->getMessage()}",
                    ]);
                }
            }

            return $commands;
        }
    }

?>
