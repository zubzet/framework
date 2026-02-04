<?php

    /**
     * ❤️ PhpStorm Intelephense
     * https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html#define-exit-points
     */

    namespace PHPSTORM_META {

        use ZubZet\Framework\ZubZet;
        use ZubZet\Framework\Core\Model;
        use ZubZet\Framework\Message\RequestResponseHandler;

        override(model(), map([
            '' => '@Model'
        ]));

        override(ZubZet::getModel(), map([
            '' => '@Model'
        ]));

        override(RequestResponseHandler::getModel(), map([
            '' => '@Model'
        ]));

        override(Model::getModel(), map([
            '' => '@Model'
        ]));

    }

?>
