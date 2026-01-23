<?php

    /**
     * ❤️ PhpStorm Intelephense
     * https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html#define-exit-points
     */

    namespace PHPSTORM_META {

        override(ZubZet\Framework\Core\Model::getModel(), map([
            '' => '@Model'
        ]));

        override(ZubZet\Framework\ZubZet::getModel(), map([
            '' => '@Model'
        ]));

        override(ZubZet\Framework\Message\RequestResponseHandler::getModel(), map([
            '' => '@Model'
        ]));

        override(model(), map([
            '' => '@Model'
        ]));
    }

?>