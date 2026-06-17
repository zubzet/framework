<?php

    namespace ZubZet\Framework\Rendering\Renderers\BladeOne;

    use eftec\bladeone\BladeOne;

    class ZubZetBladeOne extends BladeOne {

        public function __construct($templatePath = null, $compiledPath = null, $mode = 0, $commentMode = 0) {
            parent::__construct($templatePath, $compiledPath, $mode, $commentMode);

            $this->baseUrl = zubzet()->root;
        }

    }

?>
