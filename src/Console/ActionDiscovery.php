<?php

    namespace ZubZet\Framework\Console;

    class ActionDiscovery {

        /**
         * @internal
         *
         * Discover all action_ methods in all controllers by using reflection.
         *
         * @return string[][]
         */
        public static function find(string $directory): array {
            $actionsByController = [];
            foreach((new \DirectoryIterator($directory)) as $file) {
                if("php" !== $file->getExtension()) continue;

                // Find the class of the file
                $classesBefore = get_declared_classes();
                include_once $file->getPathname();
                $classesAfter = get_declared_classes();
                $controller = array_diff($classesAfter, $classesBefore);
                $controller = array_values($controller)[0];
                $controllerName = strtolower(substr($controller, 0, -10));

                // Detect all actions
                $controllerReflection = new \ReflectionClass($controller);
                $methods = $controllerReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $methods = array_filter($methods, function($method) use ($controller) {
                    $isInherited = $method->getDeclaringClass()->getName() !== $controller;
                    $isAction = str_starts_with($method->getName(), "action_");
                    return !$isInherited && $isAction;
                });

                // Store actions
                $actionsByController[$controllerName] = array_map(function($method) {
                    return strtolower(substr($method->getName(), 7));
                }, $methods);
            }

            return $actionsByController;
        }
    }

?>