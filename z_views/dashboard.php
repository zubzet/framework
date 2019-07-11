<?php function head($opt) { ?> <!-- File header -->

    <link href="<?php echo $opt["root"]; ?>assets/css/dashboardElements.css" rel="stylesheet">

<?php } function body($opt) { ?> <!-- File body -->	

    <!-- Image card -->
    <div class="row align-center columns container-padded">

        <div class="row expanded collapse">
            <div class="column">
                <div class="large-article-header no-cache-bg" data-background-image="https://unsplash.it/1132/240/?blur=1">
                    <div class="large-article-header-content">
                        <div class="center-container">
                            <div class="article-date">
                                <p class="shadowText"><?php echo $opt["date"]; ?></p>
                            </div>
                            <div class="article-title">
                                <h1 class="shadowTextBig"><?php echo $opt["lang"]("dashboard"); ?></h1>
                            </div>
                            <div class="article-details" style="color: #fefefe;">
                                <div class="article-comments" style="color: #fefefe; margin-bottom: 60px; margin-right: 20px;">
                                    <i class="fa fa-user shadowTextSmall" aria-hidden="true"></i> 
                                    <span class="shadowText"><?php echo $opt["employee"]["name"].", ".$opt["employee"]["firstName"]; ?></span>
                                </div>
                                <div class="article-comments" style="color: #fefefe; margin-bottom: 60px;">
                                    <i class="fa fa-building shadowTextSmall" aria-hidden="true"></i>
                                    <span class="shadowText"><?php echo $opt["permissionName"]; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Skills reminder -->
    <div class="callout <?php echo ($opt["updateNeeded_skills"] ? "alert" : "success"); ?>">
        <?php echo ($opt["updateNeeded_skills"] ? $opt["lang"]("please_update_skills", false) : $opt["lang"]("good_skills", false)); ?>
    </div>

    <!-- Time reminder -->
    <div class="callout <?php echo ($opt["updateNeeded_time"] ? "alert" : "success"); ?>">
        <?php echo ($opt["updateNeeded_time"] ?  $opt["lang"]("please_update_times", false) : $opt["lang"]("good_times", false)); ?>
    </div>

    <!-- Profilepicture reminder -->
    <div class="callout <?php echo ($opt["updateNeeded_profilePicture"] ? "alert" : "success"); ?>">
        <?php echo ($opt["updateNeeded_profilePicture"] ? $opt["lang"]("please_update_picture", false) : $opt["lang"]("good_picture", false)); ?>
    </div>

    <!-- Employee table -->
    <?php if ($opt["permissionLevel"] >= 1) { ?>

        <div class="row align-center columns container-padded">
            <ul class="stats-list">
                <li>
                    <?php echo $opt["employeeCount"]; ?>
                    <span class="stats-list-label"><?php echo $opt["lang"]("employees"); ?></span>
                </li>
                <li class="stats-list">
                    <?php echo $opt["loginCount"]; ?>
                    <span class="stats-list-label"><?php echo $opt["lang"]("logins"); ?></span>
                </li>
                <li class="stats-list">
                    <?php echo $opt["cvGenerateCount"]; ?>
                    <span class="stats-list-label"><?php echo $opt["lang"]("cvs_generated"); ?></span>
                </li>
            </ul>
        </div>

        <div class="row align-center container-padded">
            <div class="small-12 dashboard-table">
                <div class="columns">
                    <div class="row dashboard-table-head show-for-large">
                        <div class="large-3"><?php echo $opt["lang"]("employee"); ?></div>
                        <div class="large-3"><?php echo $opt["lang"]("email"); ?></div>
                        <div class="large-1"><?php echo $opt["lang"]("tag"); ?></div>
                        <div class="large-2"><?php echo $opt["lang"]("role"); ?></div>
                        <div class="large-1"><?php echo $opt["lang"]("has_a_password"); ?></div>
                        <div class="large-2"><?php echo $opt["lang"]("created"); ?></div>
                    </div>
                </div>
                <div class="columns">
                    <?php
                        foreach($opt["employees"] as $employee) {
                            echo '<div class="row dashboard-table-row">';
                                echo '<div class="large-3 medium-6 small-12"><span class="hide-for-large dashboard-table-label">Employee: </span>'.$employee["firstname"].' '.$employee["name"].'</div>';
                                echo '<div class="large-3 medium-6 small-12"><span class="hide-for-large dashboard-table-label">E-Mail: </span>'.$employee["email"].'</div>';
                                echo '<div class="large-1 medium-6 small-12"><span class="hide-for-large dashboard-table-label">Tag: </span>'.$employee["tag"].'</div>';
                                echo '<div class="large-2 medium-6 small-12"><span class="hide-for-large dashboard-table-label">Role: </span>'.$employee["permission"].'</div>';
                                echo '<div class="large-1 medium-6 small-12"><span class="hide-for-large dashboard-table-label">Has a password: </span>'.(!empty($employee["pw"]) ? "✓" : "✖").'</div>';
                                echo '<div class="large-2 medium-6 small-12"><span class="hide-for-large dashboard-table-label">Created: </span>'.$employee["created"].'</div>';
                            echo '</div>';
                        }
                    ?>
                </div>
            </div>
        </div>

    <?php } ?>

    <!-- Tuorial -->
    <?php if($opt["show_tutorial"]) { ?>

        <button class="button" id="skdb_tutorial_open_button" data-open="skdb_tutorial" style="display: none;">Show the tutorial</button>
        
        <div class="large reveal" id="skdb_tutorial" data-reveal>

            <h1><?php $opt["lang"]("skdb_introduction"); ?></h1>
            <p class="lead" style="margin-bottom: 30px;"><?php $opt["lang"]("tutorial_description"); ?></p>

            <div class="tutorial_part">
                <h3>
                    <?php echo file_get_contents("assets/tutorial/Dashboard/".strtolower($opt["layout_lang"])."/title.txt"); ?>
                </h3>
                <img src="<?php echo $opt["root"]; ?>assets/tutorial/Dashboard/<?php echo strtolower($opt["layout_lang"]); ?>/screenshot.jpg">
                <p>
                    <?php echo file_get_contents("assets/tutorial/Dashboard/".strtolower($opt["layout_lang"])."/description.html"); ?>
                </p>
            </div>

            <!-- Decision buttons -->
            <p>
                <button type="button" class="button" id="tutorial_understood_button" data-close><?php $opt["lang"]("understood"); ?></button>
                <button type="button" class="alert button" data-close><?php $opt["lang"]("tutorial_skip"); ?></button>
            </p>

        </div>

    <?php } ?>

    <script>

        function getNoCacheBgElements() {
            return document.querySelectorAll('.no-cache-bg');
        }

        function loadBgImageForElement(element) {
            element.style['background-image'] = 
                'url('+ element.attributes['data-background-image'].value + '?' + (new Date()).getTime() +')';
        }

        function loadBgImages() {
            for (
                var i = 0, elements = getNoCacheBgElements();
                i < elements.length;
                loadBgImageForElement(elements[i]), i++ 
            );
        }

        <?php if($opt["show_tutorial"]) { ?>
            onfoundationloaded.push(() => {
                console.log("Opening that reveal!");

                //$('#skdb_tutorial').foundation('reveal', 'open');
                setTimeout(() => {
                    $("#skdb_tutorial_open_button").trigger('click');
                }, 500);
                
            });
        <?php } ?>

        $(document).load(() => {
            loadBgImages();
        });

        //Save the tutorial progress
        <?php if($opt["show_tutorial"]) { ?>
            document.getElementById("tutorial_understood_button").addEventListener('click', e => {                        /* --  SKDB Tutorial -- */
                fetch("<?php echo $opt["root"]; ?>dashboard/finish_tutorial");
            });
        <?php } ?>
                    
    </script>

<?php } function getLangArray() {
    
    return [
        "DE_Formal" => [
            "dashboard" => "Skill-DB Dashboard",
            "employees" => "Mitarbeiter",
            "logins" => "Anmeldungen",
            "cvs_generated" => "Lebensläufe erstellt",
            "employee" => "Mitarbeiter",
            "email" => "E-Mail-Adresse",
            "tag" => "Tag",
            "role" => "Zugriffsrechte",
            "has_a_password" => "Passwort erstellt",
            "created" => "Erstellt",
            "please_update_skills" => "Bitte aktualisieren Sie Ihre Angaben zu Ihren Fähigkeiten!",
            "please_update_times" => "Bitte aktualisieren Sie Ihre Angaben zu Ihren Zeiten!",
            "please_update_picture" => "Bitte aktualisieren Sie Ihr Profilbild!",
            "good_skills" => "Ihre Angaben zu Ihren Fähigkeiten scheinen auf dem neuesten Stand zu sein. Gut gemacht!",
            "good_times" => "Ihre Angaben zu Ihren Zeiten scheinen auf dem neuesten Stand zu sein. Gut gemacht!",
            "good_picture" => "Ihr Profilbild scheint auf dem neuesten Stand zu sein. Gut gemacht!",
            "skdb_introduction" => "SKDB Einführung",
            "tutorial_description" => "Im Folgenden lernen Sie den grundlegenden Umgang mit der SKDB als Mitarbeiter. Zu jeder Bildschirmaufnahme gibt es einen erklärenden Text, welcher Ihnen die jeweilige Eingabemaske oder Funktion näher bringen soll. Sie können sich die Einführung auch später ansehen. Scrollen Sie dazu ganz nach unten und klicken Sie auf \"Ein anderes Mal anschauen\".",
            "understood" => "Verstanden und Fertig",
            "tutorial_skip" => "Ein anderes Mal anschauen"
        ], 
        "en" => [
            "dashboard" => "Skill-DB Dashboard",
            "employees" => "Employees",
            "logins" => "Logins",
            "cvs_generated" => "CV's Generated",
            "employee" => "Employee",
            "email" => "Email",
            "tag" => "Tag",
            "role" => "Role",
            "has_a_password" => "Has a password",
            "created" => "Created",
            "please_update_skills" => "Please update your skills!",
            "please_update_times" => "Please update your times!",
            "please_update_picture" => "Please update your profile picture!",
            "good_skills" => "Your skills are held up to date. Great job!",
            "good_times" => "Your times are held up to date. Great job!",
            "good_picture" => "Your profile picture is held up to date. Great job!",
            "skdb_introduction" => "SKDB Introduction",
            "tutorial_description" => "In the following you will learn the basic handling of the SKDB as an employee. For every screenshot, there is an explanatory text, which should bring you closer to the respective input mask or function. You can also skip this introduction for now. To do that, scroll down to the bottom and click on \"Skip for now\".",
            "understood" => "Understood and Done",
            "tutorial_skip" => "Skip for now"
        ]
    ];

} ?>