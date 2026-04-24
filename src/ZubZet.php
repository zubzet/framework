<?php

    namespace ZubZet\Framework;

    use ZubZet\Framework\Core\Constants;
    use ZubZet\Framework\Routing\Router;
    use ZubZet\Framework\Support\Helpers;
    use ZubZet\Framework\Message\Request;
    use ZubZet\Framework\Message\Response;
    use ZubZet\Framework\Authentication\User;
    use ZubZet\Framework\Database\Connection;
    use ZubZet\Framework\Logger\LoggerFactory;
    use ZubZet\Framework\Resources\AssetProxy;
    use ZubZet\Framework\Core\CanRetrieveModel;
    use ZubZet\Framework\Bootstrap\Configuration;
    use ZubZet\Framework\Support\GlobalReferences;
    use ZubZet\Framework\Message\Input\State as Input;
    use ZubZet\Framework\Core\CanRetrieveBooterSettings;
    use ZubZet\Framework\ErrorHandling\ExceptionBehavior;

    class ZubZet {
        use Router;
        use Configuration;
        use CanRetrieveModel;
        use ExceptionBehavior;
        use CanRetrieveBooterSettings;

        /** @var Connection $z_db Database proxy object  */
        public $z_db;

        /** @var \User $user The requesting user */
        public $user;

        /** @var \Request $req A reference to an instance of the current Request class */
        public $req;
        public array $requestStack = [];

        /** @var \Response $res A reference to an instance of the current Response class */
        public $res;
        public array $responseStack = [];

        /**
         * @internal
         * @var ZubZet The instance of the framework
         */
        public static ?ZubZet $instance = null;

        /**
         * @internal
         * @var AssetProxy The instance of the asset proxy
         */
        public AssetProxy $assetProxy;

        /**
         * Parses all the options as variables, instantiates the z_db, and establishes the db connection.
         */
        function __construct(array $params = []) {
            LoggerFactory::handleSlowRequest();

            self::$instance = $this;
            new GlobalReferences;

            $this->loadConfiguration(
                __DIR__ . DIRECTORY_SEPARATOR,
                $params,
            );

            // Error handling
            $this->setExceptionBehavior();

            $this->assetProxy = new AssetProxy;

            // Static imports
            new Constants;
            new Helpers;

            // Starting the initial state of the message system
            $this->setRequestResponse(
                new Request(Input::fromRequest()),
                new Response(),
            );

            // Import of the database connection
            $this->z_db = new Connection;

            // User
            $this->user = new User;
        }

        public function setRequestResponse(Request $request, Response $response) {
            $this->req = $request;
            $this->requestStack[] = $this->req;
            $this->res = $response;
            $this->responseStack[] = $this->res;
        }

        public function replaceRequest(Input $newState) {
            $this->req = new Request($newState);
            $this->requestStack[] = $this->req;
        }
    }

?>
