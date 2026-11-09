<?php

    namespace ZubZet\Framework\Routing;

    use FastRoute\Dispatcher;
    use FastRoute\RouteCollector;
    use FastRoute\RouteParser\Std;
    use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
    use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
    use ZubZet\Framework\Message\Input\State;
    use ZubZet\Framework\Console\Application;
    use ZubZet\Framework\Registry\Registry;
    use Symfony\Component\Console\Input\ArgvInput;
    use ZubZet\Framework\Security\CSRF;

    trait Router {

        private ?Dispatcher $routeDispatcher = null;

        /*
         * Handles the incoming request.
         * First, it tries defined FastRoute routes. If none match, it falls back to ZubZet.
         */
        public function execute(?array $customUrlParts = null) {
            if(!is_null($customUrlParts)) {
                request()->urlParts = $customUrlParts;
            }

            // CSRF defense: issue a token on every request, dispatch verifies it before the route runs.
            new CSRF();

            $dispatcher = $this->getRouteDispatcher();

            $httpMethod = request()->input->SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = request()->input->SERVER['REQUEST_URI'] ?? '/';

            // Strip query string (?foo=bar) and decode URI
            $queryPosition = strpos($uri, '?');
            if($queryPosition !== false) {
                $uri = substr($uri, 0, $queryPosition);
            }

            $uri = rawurldecode($uri);

            // Routes are defined relative to the root directory, a doubled slash after it counts as one
            $rootFolder = config("rootFolder");
            if("/" !== $rootFolder && str_starts_with("$uri/", $rootFolder)) {
                $uri = "/" . ltrim(substr($uri, strlen($rootFolder)), "/");
            }

            // Strip trailing slashes internally so /test/abc/ matches the same route as /test/abc
            if($uri !== '/') {
                $uri = rtrim($uri, '/');
            }

            // Static routes are stored lowercased, variable routes match case-insensitively
            $routeInfo = $dispatcher->dispatch($httpMethod, strtolower($uri));

            // A variable route is dispatched again with the original spelling, so its variables arrive as sent
            if(Dispatcher::FOUND === $routeInfo[0] && !empty($routeInfo[2])) {
                $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            }

            switch($routeInfo[0]) {
                // Route found, execute the handler with the extracted variables.
                case Dispatcher::FOUND:
                    $handler = $routeInfo[1];
                    $vars = $routeInfo[2];
                    return $handler($vars);

                // If no route matched, use the ZubZet Controller/Action system as a fallback.
                default:
                    // Fallback to ZubZet
                    if($this->req->isCli()) {
                        $console = Application::bootstrap($this);
                        $console->run(new ArgvInput(
                            request()->input->SERVER['argv'] ?? [],
                        ));
                        return;
                    }

                    // Perform middleware groups matching the prefix.
                    Route::performStoredGroupsMatchingPrefix(request()->getUrlParts(), function() {
                        return $this->executePath(request()->getUrlParts());
                    });

                    return;
            }
        }

        private function getRouteDispatcher(): Dispatcher {
            // Return the cached dispatcher if it exists.
            if(!is_null($this->routeDispatcher)) {
                return $this->routeDispatcher;
            }

            // Register routes and create the dispatcher on first access.
            $collector = new RouteCollector(new Std(), new GroupCountBasedDataGenerator());

            // Initialize the Route class with the current collector.
            Route::init($this, $collector);

            // Retrieve all php files from every routes root
            // (userspace, then modules in order, then framework).
            foreach(Registry::files("routes") as $file) {
                require_once $file;
            }

            // Routes match regardless of case
            [$staticRoutes, $variableRoutes] = $collector->getData();
            $staticRoutes = array_map(fn(array $routes) => array_change_key_case($routes, CASE_LOWER), $staticRoutes);

            foreach($variableRoutes as $method => $chunks) {
                foreach($chunks as $index => $chunk) {
                    // Case-insensitive flag on the combined route regex
                    $variableRoutes[$method][$index]["regex"] = $chunk["regex"] . "i";
                }
            }

            $this->routeDispatcher = new GroupCountBasedDispatcher([$staticRoutes, $variableRoutes]);
            return $this->routeDispatcher;
        }

        public function executeControllerAction($controller, $action, array $params = [], array $arguments = []) {
            $this->req->urlParameters = $params;

            $controller = strtr(urldecode($controller), Route::NAME_REPLACEMENTS);
            $method = strtr(urldecode($action), Route::NAME_REPLACEMENTS);

            try {
                $controllerFile = Registry::find("controllers", $controller);

                if ($controllerFile !== null) {
                    // Skip the include when the class already exists: a second
                    // include of a same-named file from another root would be
                    // a fatal redeclaration; first loaded wins for the request.
                    if(!class_exists($controller, false)) {
                        include_once($controllerFile);
                    }
                } else {
                    return $this->executePath(["error", "404"]);
                }
            } catch (\Exception $e) {
                return $this->executePath(["error", "500"]);
            }

            try {
                $CTRL_obj = new $controller($this->req, $this->res);
                if (method_exists($controller, $method)) {
                    return $CTRL_obj->{$method}($this->req, $this->res, ...$arguments);
                } else {
                    //Checks if the fallback method exists before rerouting to the 404 page
                    $method = "action_fallback";
                    if (method_exists($controller, $method)) {
                        return $CTRL_obj->{$method}($this->req, $this->res, ...$arguments);
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
         * it falls back to the ZubZet framework. It replaces the current request state (path and args)
         * via replaceRequest, executes the new route and then returns to the caller, which keeps running.
         *
         * @param array $parts The parts of the new route. Example: ["auth", "login"]
         */
        public function reroute(array $parts): void {
            // Below the root directory, like a real request
            $newState = State::fromOverwrite(request()->input)
                ->withPath(config("rootFolder") . ltrim(implode("/", $parts), "/"))
                ->withArgs($parts);
            zubzet()->replaceRequest($newState);
            $this->execute();
        }

        /** @var int $maxReroutes Number of reroutes the controller can perform before aborting */
        public $maxReroutes = 10;

        /** @var int $reroutes Number of times this request was rerouted */
        public $reroutes = 0;

        /**
        * Executes an action for a specified path
        * Maps [<name>, <action>] to <Name>Controller::action_<action> (defaultIndex controller / action_index when missing),
        * overwrites request()->urlParts and dies once more than $maxReroutes calls were made. Returns nothing.
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