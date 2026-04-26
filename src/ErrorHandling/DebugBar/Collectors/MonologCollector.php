<?php

    namespace ZubZet\Framework\ErrorHandling\DebugBar\Collectors;

    use Monolog\Formatter\LineFormatter;
    use DebugBar\Bridge\MonologCollector as BaseMonologCollector;

    class MonologCollector extends BaseMonologCollector {

        public function __construct() {
            parent::__construct();
            $this->setFormatter(new LineFormatter(null, 'H:i:s'));
        }

    }

?>
