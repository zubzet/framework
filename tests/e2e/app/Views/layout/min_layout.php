<?php return ["layout" => function ($opt, $body, $head) { ?>
    <!doctype html>
    <html class="no-js h-100" lang="de">
        <head>
            <meta property="og:type" content="article" />

            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>

            <script>
                Z.Lang.unsaved = "<i class='fas fa-pen text-dark'></i> Es gibt ungespeicherte Änderungen.";
                Z.Lang.submit = "<i class='fa fa-check'></i> Speichern";
                Z.Lang.saved = "<i class='fa fa-save text-dark'></i> Gespeichert!";
                Z.Lang.choose_file = "Datei Wählen";
                Z.Lang.error_filter = "Diese Eingabe entspricht nicht dem richtigen Format.";
                Z.Lang.error_length = "Diese Eingabe ist zu kurz bzw. zu lang.";
                Z.Lang.error_required = "Diese Eingabe ist erforderlich.";
                Z.Lang.error_unique = "Dieser Wert wurde bereits verwendet.";
                Z.Lang.error_exist = "Dies wurde bereits verwendet.";
            </script>
        </head>
        <body class="h-100" id="top">
            <?php $body($opt); ?>
            <?php $opt["layout_essentials_body"]($opt); ?>
        </body>
    </html>
<?php }]; ?>