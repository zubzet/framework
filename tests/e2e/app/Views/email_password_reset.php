<?php return ["body" => function ($opt) { ?>
    <h2>Das Zurücksetzen des Passworts hat funktioniert!</h2>
    <a href="<?= $opt["reset_link"]; ?>">
        Klicken Sie auf diesen Link, um Ihr Passwort zu ändern!
    </a>
    <br>
    Oder klicken Sie hier: <?= $opt["reset_link"]; ?>
<?php }]; ?>
