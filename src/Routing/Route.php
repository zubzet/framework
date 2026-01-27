<?php

    namespace ZubZet\Framework\Routing;

    use Slim\App;
    use Slim\Psr7\Response;
    use Slim\Routing\RouteContext;
    use Slim\Interfaces\RouteInterface;
    use Slim\Routing\RouteCollectorProxy;
    use Slim\Interfaces\RouteGroupInterface;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    class Route {

        use HttpMethod;

        /**
         * A stack to hold the current router context (App or RouteCollectorProxy).
         * @var (App|RouteCollectorProxy)[]
         */
        private static array $routerStack = [];

        /**
         * Stack to manage group prefixes.
         * @var string[]
         */
        private static array $prefixStack = [];

        public static array $storedPrefixedGroups = [];

        /**
         * Initializes the static Router.
         * This must be called once before loading route files.
         *
         * @param mixed $booter The main framework class for callbacks.
         */
        public static function init($booter): void {
            self::$routerStack = [$booter->slimApplication];
        }

        /**
         * Gets the current router from the top of the stack.
         * @return App|RouteCollectorProxy
         */
        private static function getCurrentRouter(): App|RouteCollectorProxy {
            if (empty(self::$routerStack)) {
                throw new \Exception("Router has not been initialized. Please call Route::init() first.");
            }
            return end(self::$routerStack);
        }

        public static function group(string $prefix = "", ?callable $callback = null): PendingGroup {
            if(is_null($callback)) $callback = function() {};
            return new PendingGroup($prefix, $callback);
        }

        /**
         * Performs middleware matching for stored groups based on the current URL parts.
         */
        public static function performStoredGroupsMatchingPrefix(array $urlParts, $callback): void {
            // Construct the current path from URL parts
            $currentPath = '/' . implode('/', $urlParts);

            // Collect middlewares to execute
            $toExecuteMiddleware = [];
            $toExecuteAfterMiddleware = [];

            foreach(self::$storedPrefixedGroups as $groupPath => $data) {
                // Check if the current path matches the group prefix
                $isMatch = ($currentPath === $groupPath) ||
                        (str_starts_with($currentPath, $groupPath . '/'));


                if(!$isMatch) continue;

                // Collect middlewares and afterwares to execute
                foreach($data['middleware'] as $mw) {
                    $toExecuteMiddleware[] = $mw;
                }

                foreach($data['afterMiddleware'] as $amw) {
                    $toExecuteAfterMiddleware[] = $amw;
                }
            }

            // Execute collected middlewares and exit if any returns any other than true
            foreach($toExecuteMiddleware as $mw) {
                [$class, $method] = $mw;
                $result = zubzet()->executeControllerAction($class, $method, []);
                if($result !== true) exit;
            }

            // Execute the main callback (route handling)
            $callback();

            // Execute after middlewares
            foreach($toExecuteAfterMiddleware as $amw) {
                [$class, $method] = $amw;
                zubzet()->executeControllerAction($class, $method, []);
            }
        }

        /**
         * Creates a route group.
         */
        public static function performGroup(string $prefix, callable $callback, array $middlewares, array $afterMiddleware): void {
            $parentRouter = self::getCurrentRouter();

            // Push the prefix onto the stack.
            self::$prefixStack[] = $prefix;

            $group = $parentRouter->group($prefix, function (RouteCollectorProxy $group) use ($callback) {
                self::$routerStack[] = $group;
                $callback();
                array_pop(self::$routerStack);
            });

            self::$storedPrefixedGroups[(implode("", self::$prefixStack))] = [
                'middleware' => $middlewares,
                'afterMiddleware' => $afterMiddleware,
            ];

            // Pop the prefix from the stack after leaving the group.
            array_pop(self::$prefixStack);

            self::performRouteInclusions($middlewares, $afterMiddleware, $group);
        }

        public static function performRoute(string $method, string $endpoint, array|callable $action, array $middlewares, array $afterMiddleware): void {
            $router = self::getCurrentRouter();

            $route = $router->$method(
                $endpoint,
                function(ServerRequestInterface $request, ResponseInterface $response, $args) use ($action) {
                    if(is_callable($action)) {
                        $action($request, $response, $args);
                    } else {
                        [$controllerClass, $actionMethod] = $action;
                        zubzet()->executeControllerAction($controllerClass, $actionMethod, $args);
                    }

                    return new Response();
                },
            );

            self::performRouteInclusions($middlewares, $afterMiddleware, $route);
        }
        private static $isCancelled = false;

        private static function performRouteInclusions(array $middlewares, array $afterMiddlewares, RouteInterface|RouteGroupInterface $routable): void {

            $routable->add(function ($request, $handler) use ($middlewares, $afterMiddlewares) {
                // Get the current route context and arguments.
                $route = RouteContext::fromRequest($request)->getRoute();
                // If the route is null, we assume no arguments are needed.
                $args = $route?->getArguments() ?? [];

                foreach($middlewares as $middleware) {
                    [$middlewareClass, $middlewareMethod] = $middleware;

                    $result = zubzet()->executeControllerAction($middlewareClass, $middlewareMethod, $args);

                    if($result === true) continue;

                    self::$isCancelled = true;

                    // If any middleware returns false, we stop processing and return an empty response.
                    return new Response();
                }

                $handler->handle($request);

                // Cancel if route processing was cancelled by middleware
                if(self::$isCancelled) {
                    return new Response();
                }

                // Applying after middlewares
                foreach($afterMiddlewares as $afterMiddleware) {
                    [$afterMiddlewareClass, $afterMiddlewareMethod] = $afterMiddleware;

                    zubzet()->executeControllerAction($afterMiddlewareClass, $afterMiddlewareMethod, $args);
                }

                return new Response();
            });

        }
    }

?>