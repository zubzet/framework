<?php

    // The class declaration intentionally uses the legacy aliases
    // (z_controller, Request, Response)
    class AliasController extends z_controller {

        // alias => canonical FQCN, taken from src/Aliases.php
        private static array $pairs = [
            "z_framework" => \ZubZet\Framework\ZubZet::class,
            "z_db" => ZubZet\Framework\Database\Connection::class,
            "z_controller" => ZubZet\Framework\Core\Controller::class,
            "z_model" => ZubZet\Framework\Core\Model::class,
            "RequestResponseHandler" => ZubZet\Framework\Message\RequestResponseHandler::class,
            "Request" => ZubZet\Framework\Message\Request::class,
            "Response" => ZubZet\Framework\Message\Response::class,
            "z_upload" => ZubZet\Framework\Form\Upload::class,
            "FormResult" => ZubZet\Framework\Form\Validation\Result::class,
            "FormField" => ZubZet\Framework\Form\Validation\Field::class,
            "Rest" => ZubZet\Framework\Support\Rest::class,
            "User" => ZubZet\Framework\Authentication\User::class,
        ];

        public function action_check(Request $req, Response $res) {
            $result = [];
            foreach(self::$pairs as $alias => $canonical) {
                $exists = class_exists($alias);
                $resolved = $exists ? (new \ReflectionClass($alias))->getName() : null;
                $result[$alias] = [
                    "canonical" => $canonical,
                    "exists" => $exists,
                    "resolvesTo" => $resolved,
                    "match" => $exists && $resolved === $canonical,
                ];
            }

            return $res->json($result);
        }
    }

?>
