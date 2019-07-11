<?php
    $la = [
        "de_formal" => [
            "administration" => "Verwaltung",
            "employees" => "Mitarbeiter",
            "add_employee" => "Mitarbeiter hinzufügen",
            "edit_employee" => "Mitarbeiter bearbeiten",
            "login_as_employee" => "Als Mitarbeiter einloggen",
            "skdb_configuration" => "SKDB Konfiguration",
            "instance" => "Instanz",
            "skills" => "Skills",
            "company" => "Unternehmen",
            "log_statistics" => "Log / Statistik",
            "edit_skills" => "Skills bearbeiten",
            "edit_time" => "Zeiten bearbeiten",
            "cv_information" => "Lebenslauf",
            "edit_personal_information" => "Persönliche Informationen bearbeiten",
            "edit_references" => "Referenzen bearbeiten",
            "edit_profile_picture" => "Profilbild bearbeiten",
            "view_cv" => "Lebenslauf ansehen",
            "publish_cv" => "Lebenslauf Veröffentlichen",
            "account_settings" => "Profileinstellungen",
            "logout" => "Ausloggen",
            "process_request" => "Anfrage bearbeiten",
            "availability_overview" => "Verfügbarkeitsübersicht",
            "token_expired_heading" => "Login Abgelaufen",
            "token_expired_explanation" => "Ihr Login ist abgelaufen. Dies bedeutet, dass Sie sich erneut anmelden müssen. Wenn Sie aktuell ungespeicherte Änderungen haben, können Sie diese noch spoeichern. Dafür müssen Sie sich allerdings zunächst erneut anmelden.",
        ],
        "en" => [
            "administration" => "Administration",
            "employees" => "Employees",
            "add_employee" => "Add employee",
            "edit_employee" => "Edit employee",
            "login_as_employee" => "Login as an employee",
            "skdb_configuration" => "SKDB Configuration",
            "instance" => "Instance",
            "skills" => "Skills",
            "company" => "Company",
            "log_statistics" => "Log / Statistics",
            "edit_skills" => "Edit skills",
            "edit_time" => "Edit time",
            "cv_information" => "CV",
            "edit_personal_information" => "Edit Personal information",
            "edit_references" => "Edit Refrences",
            "edit_profile_picture" => "Edit Profile picture",
            "view_cv" => "View CV",
            "publish_cv" => "Publish CV",
            "account_settings" => "Account settings",
            "logout" => "Logout",
            "process_request" => "Process request",
            "availability_overview" => "Availability overview",
            "token_expired_heading" => "Login Expired",
            "token_expired_explanation" => "Your login has expired. This means that you must log in again. If you currently have unsaved changes, you will be able to save them after logging in again.",
        ]
    ];
    
?>
<!doctype html>
<html class="no-js" lang="en">
    <head>
        <link rel="icon" type="image/png" href="<?php echo $opt["root"]; ?>assets/img/favicon.png">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <meta charset="utf-8" />
        <title><?php echo $opt["title"]; ?></title>
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo $opt["root"]; ?>assets/css/font-awesome.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/foundation.css">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/main.css">
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/form-icons.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
        <link href="<?php echo $opt["root"]; ?>assets/css/largeHeader.css" rel="stylesheet">
        <link href="<?php echo $opt["root"]; ?>assets/css/dashboardElements.css" rel="stylesheet">
        <!-- ESSENTIAL JS START -->
            <script src="<?php echo $opt["root"]; ?>assets/js/foundation.min.js"></script>
            <script> 
                window.onfoundationloaded = [];
                $(() => {
                    $(document).foundation();
                    for (var l of window.onfoundationloaded) l();
                });
            </script>
        <!-- ESSENTIAL JS END -->
        <?php head($opt); ?>
    </head>
    <body>
        <!-- NAVBAR START -->
        <div class="top-bar foundation-5-top-bar">
            <div class="top-bar-title">
                <span data-responsive-toggle="responsive-menu" data-hide-for="medium">
                <button class="menu-icon" type="button" data-toggle></button>
                </span>
                <a style="color:white;" href="<?php echo $opt["root"]; ?>">
                <img src="<?php echo $opt["root"]; ?>assets/css/images/logo.svg" width="150px" />
                </a>
            </div>
            <div id="responsive-menu">
                <div class="top-bar-left">
                    <ul class="dropdown vertical medium-horizontal menu" data-responsive-menu="drilldown medium-dropdown" data-auto-height="true" data-animate-height="true">
                        <?php if($opt["user"]["permissionLevel"] >= 2) { ?>
                            <li>
                                <a href="#"><?php echo $la[$opt["layout_lang"]]["administration"]; ?></a>
                                <ul class="menu vertical">
                                    <li>
                                        <a><?php echo $la[$opt["layout_lang"]]["employees"]; ?></a>
                                        <ul class="menu vertical">
                                            <li><a href="<?php echo $opt["root"]; ?>admin/add_employee"><?php echo $la[$opt["layout_lang"]]["add_employee"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>admin/edit_employee"><?php echo $la[$opt["layout_lang"]]["edit_employee"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>admin/login_as_employee"><?php echo $la[$opt["layout_lang"]]["login_as_employee"]; ?></a></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <a><?php echo $la[$opt["layout_lang"]]["skdb_configuration"]; ?></a>
                                        <ul class="menu vertical">
                                            <li><a href="<?php echo $opt["root"]; ?>admin/cfg_instance"><?php echo $la[$opt["layout_lang"]]["instance"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>admin/cfg_skills"><?php echo $la[$opt["layout_lang"]]["skills"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>admin/cfg_company"><?php echo $la[$opt["layout_lang"]]["company"]; ?></a></li>
                                        </ul>
                                    </li>
                                    <li><a href="<?php echo $opt["root"]; ?>admin/availability_overview"><?php echo $la[$opt["layout_lang"]]["availability_overview"]; ?></a></li>
                                    <li><a href="<?php echo $opt["root"]; ?>admin/log_statistics"><?php echo $la[$opt["layout_lang"]]["log_statistics"]; ?></a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if($opt["user"]["permissionLevel"] >= 0) { ?>
                            <li>
                                <a><?php echo $opt["user"]["name"]. "." . $opt["user"]["firstName"]; ?></a>
                                <ul class="menu vertical">
                                    <li><a href="<?php echo $opt["root"]; ?>settings/skills"><?php echo $la[$opt["layout_lang"]]["edit_skills"]; ?></a></li>
                                    <li><a href="<?php echo $opt["root"]; ?>settings/time"><?php echo $la[$opt["layout_lang"]]["edit_time"]; ?></a></li>
                                    <li>
                                        <a><?php echo $la[$opt["layout_lang"]]["cv_information"]; ?></a>
                                        <ul class="menu vertical">
                                            <li><a href="<?php echo $opt["root"]; ?>cv/personal_information"><?php echo $la[$opt["layout_lang"]]["edit_personal_information"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>cv/references"><?php echo $la[$opt["layout_lang"]]["edit_references"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>cv/portrait"><?php echo $la[$opt["layout_lang"]]["edit_profile_picture"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>cv/publish" target="_blanc"><?php echo $la[$opt["layout_lang"]]["publish_cv"]; ?></a></li>
                                            <li><a href="<?php echo $opt["root"]; ?>cv/view" target="_blanc"><?php echo $la[$opt["layout_lang"]]["view_cv"]; ?></a></li>
                                        </ul>
                                    </li>
                                    <li><a href="<?php echo $opt["root"]; ?>settings/account"><?php echo $la[$opt["layout_lang"]]["account_settings"]; ?></a></li>
                                    <li><a href="<?php echo $opt["root"]; ?>login/logout"><?php echo $la[$opt["layout_lang"]]["logout"]; ?></a></li>
                                </ul>
                            </li>
                            <?php } ?>
                        <?php if($opt["user"]["permissionLevel"] >= 1) { ?>
                            <li><a href="processRequest.php" style="color: red;"><?php echo $la[$opt["layout_lang"]]["process_request"]; ?></a></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- NAVBAR END -->

        <div class="top-bar-boundry"></div>

        <!-- WRAPPER START -->
        <div class="row align-center medium-10 large-10 small-11 columns container-padded">
            <?php body($opt); ?>
        </div>
        <!-- WRAPPER END -->

        <!-- TOKEN EXPIRED MESSAGE -->
        <?php if($opt["user"]["permissionLevel"] >= 0) { ?>
            <button class="button hide" data-open="tokenExpired" id="openTokenExpired"></button>
            <div class="full reveal token-expire-popup" id="tokenExpired" data-reveal>
                <h1 class="text-center"><?php echo $la[$opt["layout_lang"]]["token_expired_heading"]; ?></h1>
                <div class="row">
                    <div class="medium-2 show-for-medium"></div>
                    <div class="mdeium-8 small-12 small-centered">
                        <p class="lead text-center"><?php echo $la[$opt["layout_lang"]]["token_expired_explanation"]; ?></p>
                    </div>
                </div>
                <iframe id="loginFrame" class="login-frame" width="100%"></iframe>
                <button id="closeTokenExpired" class="close-button hide" data-close aria-label="Close reveal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <script>
                var timedOut = false;

                var iframe = document.getElementById("loginFrame");
                iframe.onload = () => {
                    if (iframe.contentWindow.document.body) {       
                        iframe.height = (iframe.contentWindow.document.body.scrollHeight + 10) + "px";
                    }
                }

                var token_expired_callback = setInterval(function() {
                    if (!timedOut) {
                        if(document.cookie.indexOf("skdb_login_token") < 0) {
                            timedOut = true;
                            $("#openTokenExpired").click();
                            $("#loginFrame").attr("src", '<?php echo $opt["root"]; ?>?noLayout=true');
                        }
                    }
                    if (timedOut) {
                        if(document.cookie.indexOf("skdb_login_token") >= 0) {
                            timedOut = false;
                            $("#closeTokenExpired").click();
                        }
                    }
                }, 1000);
            </script>
        <?php } ?>
        <!-- TOKEN EXPIRED MESSAGE -->
    </body>
</html>