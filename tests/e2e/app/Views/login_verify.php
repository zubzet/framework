<?php return ["head" => function($opt) { ?>
	<link rel="stylesheet" href="<?= $opt["root"]; ?>assets/css/loadCircle.css">
<?php }, "body" => function($opt) { ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 col-12 p-0">
                <div class="card bg-darker shadow-sm">
                    <div class="panel panel-default">
                        <div class="main-form p-4">
                            <p class="text-center">
                                <?php if ($opt["success"]) { ?>
                                    Deine Bestätigung war <?= ($opt["success"] ? "" : "<b>nicht</b> "); ?>erfolgreich!<br>
                                <?php } else { ?>
                                    Vermisst du die Bestätigungsmail? Fordere einfach eine Neue an.
                                    <form action="" method="POST">
                                        <div class="input-group" title="E-Mail">
                                            <input name="email" id="input-email" class="form-control" type="email" placeholder="Deine E-Mail">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">Senden</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php } ?>
                            </p>
                            <div class="text-center">
                                <a href="<?= $opt["root"]; ?>login">
                                    <i class="fa fa-sign-in"></i>
                                    Zurück zum Login
                                </a>
                            </div>
                            <hr>
                            <div class="text-center mb-0">
                                <a href="<?= $opt["root"]; ?>login/signup">
                                    Du hast noch keinen Account?
                                </a>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>   
        </div>
    </div>
	<div class="loading" id="loading" style="display: none;">Lädt&#8230;</div>
<?php }]; ?>