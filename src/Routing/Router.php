<?php

    namespace ZubZet\Framework\Routing;

    use ZubZet\Framework\Console\Application;

    use Slim\App as SlimRouter;
    use Slim\Factory\AppFactory;
    use Slim\Exception\HttpNotFoundException;
    use Slim\Psr7\Response as HttpResponse;
    use Slim\Factory\ServerRequestCreatorFactory;

    trait Router {

        private SlimRouter $slimRouter;

        /*
         * Handles the incoming request
         * Firstly, it tries to load routes from Slim, then it falls back to the ZubZet framework.
         */
        public function execute(?array $customUrlParts = null) {
            if(!is_null($customUrlParts)) {
                request()->urlParts = $customUrlParts;
            }

            // Create a new Slim App instance
            $this->slimRouter = AppFactory::create();

            // Initialize the Route class with the Slim app and this framework instance
            Route::init($this, $this->slimRouter);

            // Get all php files in the z_routes directory
            $routeFiles = glob($this->routes . "/*.php");

            // require them to register the routes
            foreach ($routeFiles as $file) {
                require_once $file;
            }

            // Execute the Slim Route
            try  {
                $this->slimRouter->run();
            } catch (HttpNotFoundException $e) {
                // Fallback to ZubZet
                if($this->req->isCli()) {
                    $console = Application::bootstrap($this);
                    $console->run();
                    return;
                }

                // it should perform the middleware groups matching the prefix
                Route::performStoredGroupsMatchingPrefix(request()->getUrlParts(), function() {
                    return $this->executePath(request()->getUrlParts());
                });

                // Return a PSR response to stop further processing
                return new HttpResponse();
            }
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
         * Tries to reroute the request using Slim first, if the route does not exist, it falls back to the ZubZet framework.
         *
         * @param mixed $parts The parts of the new route. Example: ["auth", "login"]
         * @return void
         */
        public function reroute($parts) {
            try {
                $joinedParts = implode("/", $parts);

                $request = ServerRequestCreatorFactory::create()->createServerRequestFromGlobals();

                $request = $request->withUri(
                    $request->getUri()->withPath(
                        "/$joinedParts"
                    )
                );

                $this->slimRouter->run($request);
            } catch(HttpNotFoundException $e) {
                // Fallback to ZubZet
                $this->executePath($parts);
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