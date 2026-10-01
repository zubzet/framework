<?php

    namespace ZubZet\Framework\Tasks;

    use ZubZet\Framework\Registry\Registry;

    /**
     * @internal
     *
     * Turns the type stored on a task row back into an instance. Task files
     * resolve through the registry like controllers and models do, so an
     * application task shadows a module task of the same name.
     */
    final class TaskResolver {

        /** The file declaring a task, or null when nothing declares it. */
        public static function locate(string $type): ?string {
            // Task names come from the database; keep the lookup to plain class
            // names so a crafted row cannot address arbitrary paths.
            if(1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $type)) return null;

            return Registry::find("tasks", $type);
        }

        /**
         * Loads and instantiates a task.
         *
         * @throws \RuntimeException When no file declares the task, or the class
         *     it declares is not a Task.
         */
        public static function instantiate(string $type): Task {
            $file = self::locate($type);
            if(is_null($file)) {
                throw new \RuntimeException("Task '$type' does not exist in any app/Tasks directory.");
            }

            if(!class_exists($type, false)) {
                require_once $file;
            }

            if(!class_exists($type, false)) {
                throw new \RuntimeException("File '$file' does not declare a class named '$type'.");
            }

            if(!is_subclass_of($type, Task::class)) {
                throw new \RuntimeException("Task '$type' must extend " . Task::class . ".");
            }

            return new $type();
        }
    }

?>
