<?php return [ "head" => function() { ?>

    <style>
        iframe {
            border: none;
            width: 100%;
            height: 100%;
            min-height: 85vh;
        }

        .content {
            margin-left: 0px !important;
            margin-right: 0px !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0px !important;
        }
    </style>

<?php }, "body" => function($opt) { ?>	

    <iframe src="<?= $opt["root"] ?>z/database/internal"></iframe>

<?php }]; ?>