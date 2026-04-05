<?php

    namespace ZubZet\Framework;

    use ZubZet\Framework\Routing\Router;
    use ZubZet\Framework\Core\Constants;
    use ZubZet\Framework\Support\Helpers;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Core\CanRetrieveModel;
    use ZubZet\Framework\Bootstrap\Configuration;
    use ZubZet\Framework\Support\GlobalReferences;
    use ZubZet\Framework\ErrorHandling\ExceptionBehavior;

    class ZubZet {
        use Router;
        use Configuration;
        use CanRetrieveModel;
        use ExceptionBehavior;

        /** @var Connection $z_db Database proxy object  */
        public $z_db;

        /** @var \User $user The requesting user */
        public $user;

        /** @var \Response $res A reference to an instance of the Response class */
        public $res;

        /** @var \Request $req A reference to an instance of the Request class */
        public $req;

        /**
         * @internal
         * @var ZubZet The instance of the framework
         */
        public static ?ZubZet $instance = null;

        /**
         * Parses all the options as variables, instantiates the z_db, and establishes the db connection.
         */
        function __construct(array $params = []) {
            self::$instance = $this;
            new GlobalReferences;

            $this->loadConfiguration(
                __DIR__ . DIRECTORY_SEPARATOR,
                $params,
            );

            // Error handling
            $this->setExceptionBehavior();

            // Static Imports
            new Constants;
            new Helpers;

            //Parse Post request
            array_walk_recursive($_POST, function(&$item) {
                if(substr($item, 0, 10) == "<#decb64#>") {
                    $item = substr($item, 10);
                    $item = base64_decode($item);
                }
                if(substr($item, 0, 10) == "<#decURI#>") {
                    $item = substr($item, 10);
                    $item = rawurldecode($item);
                }
            });

            //processing the url
            $this->url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "cli");

            $path = parse_url($this->url, PHP_URL_PATH) ?: "";
            $path = trim($path, '/');
            $this->rootDirectory = trim($this->rootDirectory, '/');

            $urlParts = $path !== "" ? explode("/", $path) : [];

            $this->rootDirectory = $this->rootDirectory !== "" ? explode("/", $this->rootDirectory) : [];
            for ($i = 0; $i < count($this->rootDirectory); $i++) array_shift($urlParts);

            $this->urlParts = $urlParts;

            // Message System
            $this->req = new Request($this);
            $this->res = new Response($this);

            // Import of the database connection
            $this->z_db = new Connection($this);

            // User
            $this->user = new User;
        }

    }

?>
