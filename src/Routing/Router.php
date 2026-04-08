<?php

    namespace ZubZet\Framework\Routing;

    use FastRoute;
    use FastRoute\RouteCollector;
    use ZubZet\Framework\Console\Application;

    trait Router {

        private ?FastRoute\Dispatcher $routeDispatcher = null;

        /*
         * Handles the incoming request.
         * First, it tries defined FastRoute routes. If none match, it falls back to ZubZet.
         */
        public function execute(?array $customUrlParts = null) {
            if(!is_null($customUrlParts)) {
                request()->urlParts = $customUrlParts;
            }

            $dispatcher = $this->getRouteDispatcher();

            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = $_SERVER['REQUEST_URI'] ?? '/';

            // Strip query string (?foo=bar) and decode URI
            $queryPosition = strpos($uri, '?');
            if($queryPosition !== false) {
                $uri = substr($uri, 0, $queryPosition);
            }

            $uri = rawurldecode($uri);
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

            switch($routeInfo[0]) {
                // Route found, execute the handler with the extracted variables.
                case FastRoute\Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];
                    return $handler($vars);

                // If no route matched, use the ZubZet Controller/Action system as a fallback.
                default:
                    // Fallback to ZubZet
                    if($this->req->isCli()) {
                        $console = Application::bootstrap($this);
                        $console->run();
                        return;
                    }

                    // Perform middleware groups matching the prefix.
                    Route::performStoredGroupsMatchingPrefix(request()->getUrlParts(), function() {
                        return $this->executePath(request()->getUrlParts());
                    });

                    return;
            }
        }

        private function getRouteDispatcher(): FastRoute\Dispatcher {
            // Return the cached dispatcher if it exists.
            if(!is_null($this->routeDispatcher)) {
                return $this->routeDispatcher;
            }

            // Register routes and create the dispatcher on first access.
            $this->routeDispatcher = FastRoute\simpleDispatcher(function(RouteCollector $collector) {
                // Initialize the Route class with the current collector.
                Route::init($this, $collector);

                // Retrieve all php files in the routes directory
                $routeFiles = glob($this->routes . "/*.php");

                // Include each route file to register its routes
                foreach($routeFiles as $file) {
                    require_once $file;
                }
            });

            return $this->routeDispatcher;
        }

        public function executeControllerAction($controller, $action, array $params = []) {
            $this->req->urlParameters = $params;

            $controller = urldecode($controller);
            $method = urldecode($action);

            $actionPatternReplacement = [
                ["-", "_"],
                [".", "§2E"],
                ["ä", "ae"],
                ["ö", "oe"],
                ["ü", "ue"]
            ];

            foreach($actionPatternReplacement as $apr) {
                $method = str_replace($apr[0], $apr[1], $method);
                $controller = str_replace($apr[0], $apr[1], $controller);
            }

            try {
                $controllerFile = null;
                if (file_exists($this->z_controllers . $controller . ".php")) {
                    $controllerFile = $this->z_controllers . $controller . ".php";
                } else if (file_exists($this->z_framework_root . "IncludedComponents/controllers/" . $controller . ".php")) {
                    $controllerFile = $this->z_framework_root . "IncludedComponents/controllers/" . $controller . ".php";
                }

                if ($controllerFile !== null) {
                    include_once($controllerFile);
                } else {
                    return $this->executePath(["error", "404"]);
                }
            } catch (\Exception $e) {
                return $this->executePath(["error", "500"]);
            }

            try {
                $CTRL_obj = new $controller($this->req, $this->res);
                if (method_exists($controller, $method)) {
                    return $CTRL_obj->{$method}($this->req, $this->res);
                } else {
                    //Checks if the fallback method exists before rerouting to the 404 page
                    $method = "action_fallback";
                    if (method_exists($controller, $method)) {
                        return $CTRL_obj->{$method}($this->req, $this->res);
                    } else {
                        return $this->executePath(["error", "404"]);
                    }
                }
            } catch(\Exception $e) {
                if ($this->showErrors != 0) {
                    throw $e;
                } else {
                    return $this->executePath(["error", "500"]);
                }
            }
        }

        /**
         * Tries to reroute the request using FastRoute first, if the route does not exist,
         * it falls back to the ZubZet framework.
         *
         * @param mixed $parts The parts of the new route. Example: ["auth", "login"]
         */
        public function reroute($parts) {
            if(!is_array($parts)) {
                $parts = explode("/", trim((string) $parts, "/"));
            }

            $parts = array_values(array_filter($parts, fn($part) => $part !== ""));

            $joinedParts = implode("/", $parts);
            $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = '/' . ltrim($joinedParts, '/');

            $routeInfo = $this->getRouteDispatcher()->dispatch($httpMethod, $uri);

            switch($routeInfo[0]) {
                case FastRoute\Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];
                    $handler($vars);
                    return;

                default:
                    // Fallback to ZubZet
                    $this->executePath($parts);
                    return;
            }
        }

        /** @var int $maxReroutes Number of reroutes the controller can perform before aborting */
        public $maxReroutes = 10;

        /** @var int $reroutes Number of times this request was rerouted */
        public $reroutes = 0;

        /**
        * Executes an action for a specified path
        * @param array $parts Example: ["auth", "login"]
        */
        public function executePath($parts) {
            request()->urlParts = $parts;

            $this->reroutes++;
            if($this->reroutes > $this->maxReroutes) {
                die("Error: Too many reroutes. Please contact the webmaster.");
            }

            if(isset($parts[0])) {
                $controller = ucfirst($parts[0]) . 'Controller';
            } else {
                $controller = config("defaultIndex", default: "DashboardController");
            }

            if(isset($parts[1])) {
                $method = "action_" . strtolower($parts[1]);
            } else {
                $method = "action_index";
            }

            $this->executeControllerAction($controller, $method);
        }

    }

?>