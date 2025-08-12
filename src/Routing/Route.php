<?php

namespace ZubZet\Framework\Routing;

use Exception;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ServerRequestInterface as sRequest;
use Psr\Http\Message\ResponseInterface as sResponse;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Routing\RouteContext;

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
                throw new Exception("Router has not been initialized. Please call Route::init() first.");
            }
            return end(self::$routerStack);
        }

        static function any(string $endpoint, array $action): PendingRoute {return new PendingRoute("any", $endpoint, $action);}
        static function get(string $endpoint, array $action): PendingRoute {return new PendingRoute('get', $endpoint, $action);}
        public static function post(string $endpoint, array $action): PendingRoute { return new PendingRoute('post', $endpoint, $action); }
        public static function put(string $endpoint, array $action): PendingRoute { return new PendingRoute('put', $endpoint, $action); }
        public static function delete(string $endpoint, array $action): PendingRoute { return new PendingRoute('delete', $endpoint, $action); }
        public static function patch(string $endpoint, array $action): PendingRoute { return new PendingRoute('patch', $endpoint, $action); }
        public static function options(string $endpoint, array $action): PendingRoute { return new PendingRoute('options', $endpoint, $action); }
        public static function define(string $method, string $endpoint, array $action): PendingRoute { return new PendingRoute($method, $endpoint, $action); }



        static function group(string $prefix, callable $callback): PendingGroup {
            return new PendingGroup($prefix, $callback);
        }

        static function performFallback(string $endpoint, string $method, array $action, array ...$middlewares): void {
            $fullPrefix = implode('', self::$prefixStack);

            // Saves the fallback details for later registration.
            self::$deferredFallbacks[] = [
                'method'   => 'get',
                'prefix'   => $fullPrefix,
                'endpoint' => $endpoint,
                'action'   => $action,
                'middlewares' => $middlewares
            ];
        }

        public static function registerDeferredFallbacks(): void {
            usort(self::$deferredFallbacks, function ($a, $b) {
                $countA = substr_count($a['prefix'] . $a['endpoint'], '/');
                $countB = substr_count($b['prefix'] . $b['endpoint'], '/');
                return $countB <=> $countA; // Absteigend sortieren
            });

            foreach (self::$deferredFallbacks as $fallback) {
                $fullPath = $fallback['prefix'] . $fallback['endpoint'];
                self::performRoute(
                    $fallback['method'],
                    $fullPath,
                    $fallback['action'],
                    ...$fallback['middlewares']
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
                return new \Slim\Psr7\Response();
            });

            self::performMiddlewareChecks($middlewares, $route);
        }

        private static function performMiddlewareChecks(array $middlewares, RouteInterface|RouteGroupInterface $routable): void {
            foreach($middlewares as $middleware) {
                [$middlewareClass, $middlewareMethod] = $middleware;

                $routable->add(function ($request, $handler) use ($middlewareClass, $middlewareMethod) {
                    // Get the current route context and arguments.
                    $route = RouteContext::fromRequest($request)->getRoute();
                    // If the route is null, we assume no arguments are needed.
                    $args = $route?->getArguments() ?? [];

                    $result = self::$booter->executeControllerAction($middlewareClass, $middlewareMethod, $args);

                    if($result === true) return $handler->handle($request);

                    return new \Slim\Psr7\Response();
                });
            }
        }
    }

?>