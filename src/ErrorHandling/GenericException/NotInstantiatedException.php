<?php

    namespace ZubZet\Framework\ErrorHandling\GenericException;

    class NotInstantiatedException extends \LogicException {

        public function __construct(string $targetName) {
            parent::__construct("The requested instance '$targetName' has not yet been setup.");
        }

    }

?>