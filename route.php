<?php

    use Slim\App;
    use Slim\Routing\RouteCollectorProxy;
    use Psr\Http\Message\ServerRequestInterface as sRequest;
    use Psr\Http\Message\ResponseInterface as sResponse;
    use Slim\Interfaces\RouteInterface;
    use Slim\Interfaces\RouteGroupInterface;

    class PendingRoutingState {

        public array $middleware = [];

        public function middleware(array $middleware): self {
            $this->middleware[] = $middleware;
            return $this;
        }
    }

    class PendingRoute extends PendingRoutingState{

        public function __construct(
            private string $method,
            private string $endpoint,
            private array $action,
        ) {}

        public function __destruct() {
            if(str_ends_with($this->endpoint, '/*')) {
                $this->endpoint = substr_replace($this->endpoint, '{param:.*}', -1);
            }

            Route::performRoute(
                $this->method,
                $this->endpoint,
                $this->action,
                ...$this->middleware
            );
        }
    }

    class PendingGroup extends PendingRoutingState {

        private string $prefix;
        private $callback;

        public function __construct(string $prefix, callable $callback) {
            $this->prefix = $prefix;
            $this->callback = $callback;
        }

        public function __destruct() {
            Route::performGroup(
                $this->prefix, 
                $this->callback, 
                ...$this->middleware
            );
        }
    }


    class Route {

        /**
         * The framework bootstrapper instance.
         * @var z_framework|null
         */
        private static $booter = null;

        /**
         * A stack to hold the current router context (App or RouteCollectorProxy).
         * @var (App|RouteCollectorProxy)[]
         */
        private static array $routerStack = [];

        /**
         * Array to store fallback routes.
         * @var array
         */
        private static array $deferredFallbacks = [];

        /**
         * Stack to manage group prefixes.
         * @var string[]
         */
        private static array $prefixStack = [];


        /**
         * Initializes the static Router.
         * This must be called once before loading route files.
         *
         * @param App $app The main Slim App instance.
         * @param mixed $booter The main framework class for callbacks.
         */
        public static function init(App $app, $booter): void {
            self::$booter = $booter;
            self::$routerStack = [$app];
        }

        /**
         * Gets the current router from the top of the stack.
         * @return App|RouteCollectorProxy
         */
        private static function getCurrentRouter(): App|RouteCollectorProxy {
            if (empty(self::$routerStack)) {
                throw new LogicException("Router has not been initialized. Please call Route::init() first.");
            }
            return end(self::$routerStack);
        }

        static function any(string $endpoint, array $action): PendingRoute {return new PendingRoute("any", $endpoint, $action);}
        static function get(string $endpoint, array $action): PendingRoute {return new PendingRoute('get', $endpoint, $action);}
        public static function post(string $endpoint, array $action): PendingRoute { return new PendingRoute('post', $endpoint, $action); }
        public static function put(string $endpoint, array $action): PendingRoute { return new PendingRoute('put', $endpoint, $action); }
        public static function delete(string $endpoint, array $action): PendingRoute { return new PendingRoute('delete', $endpoint, $action); }
        public static function patch(string $endpoint, array $action): PendingRoute { return new PendingRoute('patch', $endpoint, $action); }



        static function group(string $prefix, callable $callback): PendingGroup {
            return new PendingGroup($prefix, $callback);
        }

        static function performFallback(string $endpoint, array $action) {
            $fullPrefix = implode('', self::$prefixStack);

            // Saves the fallback details for later registration.
            self::$deferredFallbacks[] = [
                'prefix'   => $fullPrefix,
                'endpoint' => $endpoint,
                'action'   => $action
            ];
        }

        public static function registerDeferredFallbacks(): void {
            foreach (self::$deferredFallbacks as $fallback) {
                // Combine the stored prefix and the endpoint.
                $fullPath = $fallback['prefix'] . $fallback['endpoint'];
                self::performRoute(
                    'get',
                    $fullPath, // Add the wildcard pattern
                    $fallback['action']
                );
            }
        }

        /**
         * Creates a route group.
         */
        static function performGroup(string $prefix, callable $callback, array ...$middlewares): void {
            $parentRouter = self::getCurrentRouter();

            // Push the prefix onto the stack.
            self::$prefixStack[] = $prefix;

            $group = $parentRouter->group($prefix, function (RouteCollectorProxy $group) use ($callback) {
                self::$routerStack[] = $group;
                $callback();
                array_pop(self::$routerStack);
            });

            // Pop the prefix from the stack after leaving the group.
            array_pop(self::$prefixStack);

            self::performMiddlewareChecks($middlewares, $group);
        }

        public static function performRoute(string $method, string $endpoint, array $action, array ...$middlewares): void {
            [$controllerClass, $actionMethod] = $action;
            $router = self::getCurrentRouter();

            $route = $router->$method($endpoint, function (sRequest $request, sResponse $response, $args) use ($controllerClass, $actionMethod) {
                self::$booter->executeControllerAction($controllerClass, $actionMethod, $args);
                exit;
            });

            self::performMiddlewareChecks($middlewares, $route);
        }

        private static function performMiddlewareChecks(array $middlewares, RouteInterface|RouteGroupInterface $routable): void {
            foreach($middlewares as $middleware) {
                [$middlewareClass, $middlewareMethod] = $middleware;

                $routable->add(function ($request, $handler) use ($middlewareClass, $middlewareMethod) {
                    $result = self::$booter->executeControllerAction($middlewareClass, $middlewareMethod);

                    if($result !== true) {
                        $res = new \Slim\Psr7\Response();
                        $res->getBody()->write("Middleware $middlewareClass::$middlewareMethod denied access");
                        return $res->withStatus(403);
                    }

                    return $handler->handle($request);
                });
            }
        }
    }
?>