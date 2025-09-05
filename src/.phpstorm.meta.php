<?php

    /**
     * ❤️ PhpStorm Intelephense
     * https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html#define-exit-points
     */

    namespace PHPSTORM_META {

        override(\z_model::getModel(), map([
            '' => '@Model'
        ]));

        override(\z_framework::getModel(), map([
            '' => '@Model'
        ]));

        override(\RequestResponseHandler::getModel(), map([
            '' => '@Model'
        ]));

    }

?>