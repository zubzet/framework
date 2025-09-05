<?php

/**
 * The password reset view
 */

return ["head" => function ($opt) { ?>
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/loadCircle.css") ?>">
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/main.css") ?>">

<?php }, "body" => function ($opt) { ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 col-12 p-0">
                <div class="card shadow-sm mb-3">
                    <div class="card-body bg-light">
                        <h1 class="h4 mb-3">
                            <i class="fas fa-key"></i>
                            Passwort zurücksetzen
                        </h1>
                        <div id="reset-error-label" class="alert alert-danger text-center" style="display: none;" role="alert">
                            Passwörter stimmen nicht überein!
                        </div>
                        <form action="" method="post" id="form">
                            <div class="input-group mb-3 mr-sm-2" title="Passwort">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="fa fa-key" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <input name="password" type="password" id="input-password" class="form-control" placeholder="Passwort" minlength="4" required>
                            </div>

                            <div class="input-group mb-3 mr-sm-2" title="Passwort wiederholen">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="fa fa-key" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <input type="password" id="input-password-repeat" class="form-control" placeholder="Passwort wiederholen" minlength="4" required>
                            </div>

                            <button class="btn btn-primary btn-block my-3" id="reset-btn">
                                <i class="fa fa-undo mr-1"></i>
                                Zurücksetzen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("input-password").addEventListener("change", check);
        document.getElementById("input-password-repeat").addEventListener("change", check);

        function check() {
            const password = document.getElementById("input-password").value;
            const password2 = document.getElementById("input-password-repeat").value;
            const isValid = password === password2;

            document.getElementById("reset-error-label").style.display = isValid ? "none" : "block";
            document.getElementById("reset-btn").disabled = !isValid;
        }
    </script>
<?php }]; ?>
