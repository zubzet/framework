<?php

    namespace ZubZet\Framework\Routing;

    use FastRoute\RouteCollector;
    use Request;
    use Response;
    use ZubZet\Framework\ZubZet;

    class Route {

        use HttpMethod;

        /**
         * The active FastRoute collector.
         */
        private static ?RouteCollector $collector = null;

        /**
         * Stack to manage group prefixes.
         * @var string[]
         */
        private static array $prefixStack = [];

        /**
         * Stack to manage middleware inheritance of nested groups.
         * @var array<int, array{middleware: array, afterMiddleware: array}>
         */
        private static array $groupStateStack = [];

        public static array $storedPrefixedGroups = [];

        /**
         * Initializes the static Router.
         * This must be called once before loading route files.
         */
        public static function init(ZubZet $booter, RouteCollector $collector): void {
            self::$collector = $collector;
            self::$prefixStack = [];
            self::$groupStateStack = [];
            self::$storedPrefixedGroups = [];
        }

        /**
         * Gets the current route collector.
         */
        private static function getCurrentRouter(): RouteCollector {
            if (self::$collector === null) {
                throw new \LogicException("Route collector has not been initialized.");
            }
            return self::$collector;
        }

        public static function group(string $prefix = "", ?callable $callback = null): PendingGroup {
            if(is_null($callback)) $callback = function() {};
            return new PendingGroup($prefix, $callback);
        }

        /**
         * Performs middleware matching for stored groups based on the current URL parts.
         */
        public static function performStoredGroupsMatchingPrefix(array $urlParts, \Closure|callable $callback): void {
            // Construct the current path from URL parts
            $currentPath = '/' . implode('/', $urlParts);

            // Collect middlewares to execute
            $toExecuteMiddlewares = [];
            $toExecuteAfterMiddlewares = [];

            foreach(self::$storedPrefixedGroups as $groupPath => $prefixGroup) {
                // Check if the current path matches the group prefix
                $isExactMatch = $currentPath === $groupPath;
                $isMatch = $isExactMatch || str_starts_with($currentPath, $groupPath . '/');
                if(!$isMatch) continue;

                // Collect middlewares and afterwares to execute
                foreach($prefixGroup["middleware"] as $middleware) {
                    $toExecuteMiddlewares[] = $middleware;
                }

                foreach($prefixGroup["afterMiddleware"] as $afterMiddleware) {
                    $toExecuteAfterMiddlewares[] = $afterMiddleware;
                }
            }

            // Execute collected middlewares and exit if any returns any other than true
            foreach($toExecuteMiddlewares as $toExecuteMiddleware) {
                [$class, $method] = $toExecuteMiddleware;
                $arguments = array_slice($toExecuteMiddleware, 2); // Extract additional arguments if provided
                $result = self::callControllerAction($class, $method, arguments: $arguments);
                if($result !== true) exit;
            }

            // Execute the main callback (route handling)
            $callback();

            // Execute after middlewares
            foreach($toExecuteAfterMiddlewares as $toExecuteAfterMiddleware) {
                [$class, $method] = $toExecuteAfterMiddleware;
                $arguments = array_slice($toExecuteAfterMiddleware, 2); // Extract additional arguments if provided
                self::callControllerAction($class, $method, arguments: $arguments);
            }
        }

        /**
         * Creates a route group.
         */
        public static function performGroup(string $prefix, callable $callback, array $middlewares, array $afterMiddleware): void {
            // Push the prefix onto the stack.
            self::$prefixStack[] = $prefix;

            // Store fallback middleware behavior by prefix path.
            self::$storedPrefixedGroups[(implode("", self::$prefixStack))] = [
                'middleware' => $middlewares,
                'afterMiddleware' => $afterMiddleware,
            ];

            // Push inherited group middleware state.
            self::$groupStateStack[] = [
                'middleware' => $middlewares,
                'afterMiddleware' => $afterMiddleware,
            ];

            $callback();

            array_pop(self::$groupStateStack);
            array_pop(self::$prefixStack);
        }

        public static function performRoute(string $method, string $endpoint, array|callable $action, array $middlewares, array $afterMiddleware): void {
            $router = self::getCurrentRouter();

            $effectiveMiddlewares = [
                ...self::getInheritedMiddlewares(),
                ...$middlewares,
            ];

            $effectiveAfterMiddlewares = [
                ...$afterMiddleware,
                ...self::getInheritedAfterMiddlewares(),
            ];

            $handler = function(array $args) use ($action, $effectiveMiddlewares, $effectiveAfterMiddlewares) {
                foreach($effectiveMiddlewares as $middleware) {
                    [$middlewareClass, $middlewareMethod] = $middleware;
                    $arguments = array_slice($middleware, 2); // Extract additional arguments if provided

                    // Execute the middleware and check its result.
                    $result = self::callControllerAction($middlewareClass, $middlewareMethod, $args, $arguments);

                    // Stop processing if middleware returns any other than true.
                    if($result !== true) {
                        return;
                    }
                }

                // Execute the main action, which can be either a callable or a controller action.
                if(is_callable($action)) {
                    self::performCallableAction($action, $args);
                } else {
                    [$controllerClass, $actionMethod] = $action;
                    $arguments = array_slice($action, 2); // Extract additional arguments if provided
                    self::callControllerAction($controllerClass, $actionMethod, $args, $arguments);
                }

                // After the main action, execute after middlewares.
                foreach($effectiveAfterMiddlewares as $afterMiddlewareState) {
                    [$afterMiddlewareClass, $afterMiddlewareMethod] = $afterMiddlewareState;
                    $arguments = array_slice($afterMiddlewareState, 2); // Extract additional arguments if provided
                    self::callControllerAction($afterMiddlewareClass, $afterMiddlewareMethod, $args, $arguments);
                }
            };

            // Register the route with FastRoute.
            $router->addRoute(
                self::resolveMethod($method),
                self::buildEndpoint($endpoint),
                $handler,
            );
        }

        private static function resolveMethod(string $method): string|array {
            $method = strtoupper($method);

            // Support "ANY" as a special method that maps to all HTTP methods.
            if($method === "ANY") {
                return ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD"];
            }

            return $method;
        }

        private static function buildEndpoint(string $endpoint): string {
            $prefix = implode("", self::$prefixStack);
            $path = $prefix . $endpoint;

            if($path === "") {
                return "/";
            }

            $path = "/" . ltrim($path, "/");
            $path = preg_replace('#/+#', '/', $path) ?? $path;

            if($path !== "/") {
                $path = rtrim($path, "/");
            }

            return $path;
        }

        /**
         * Keeps callback compatibility with route closures using 0..3 parameters.
         */
        private static function performCallableAction(callable $action, array $args): void {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($action));

            $resolved = array_map(function(\ReflectionParameter $param) use ($args) {
                $type = $param->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                return match(true) {
                    $typeName === 'array' => $args,
                    is_a($typeName, Request::class, true) => request(),
                    is_a($typeName, Response::class, true) => response(),
                    $param->isDefaultValueAvailable() => $param->getDefaultValue(),
                    default => null,
                };
            }, $reflection->getParameters());

            $action(...$resolved);
        }

        private static function getInheritedMiddlewares(): array {
            $middlewares = [];

            foreach(self::$groupStateStack as $groupState) {
                foreach($groupState["middleware"] as $middleware) {
                    $middlewares[] = $middleware;
                }
            }

            return $middlewares;
        }

        private static function getInheritedAfterMiddlewares(): array {
            $afterMiddlewares = [];

            // Group after-middlewares run from inner group to outer group.
            foreach(array_reverse(self::$groupStateStack) as $groupState) {
                foreach($groupState["afterMiddleware"] as $afterMiddleware) {
                    $afterMiddlewares[] = $afterMiddleware;
                }
            }

            return $afterMiddlewares;
        }

        private static function callControllerAction(string $class, string $method, array $args = [], array $arguments = []): mixed {
            return zubzet()->executeControllerAction($class, $method, $args, $arguments);
        }
    }

?>