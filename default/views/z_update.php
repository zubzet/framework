<?php 
/**
 * The user select view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->
    <style>
    .spinner {
    margin: 100px auto;
    width: 50px;
    height: 40px;
    text-align: center;
    font-size: 10px;
    }

    .spinner > div {
    background-color: #333;
    height: 100%;
    width: 6px;
    display: inline-block;
    
    -webkit-animation: sk-stretchdelay 1.2s infinite ease-in-out;
    animation: sk-stretchdelay 1.2s infinite ease-in-out;
    }

    .spinner .rect2 {
    -webkit-animation-delay: -1.1s;
    animation-delay: -1.1s;
    }

    .spinner .rect3 {
    -webkit-animation-delay: -1.0s;
    animation-delay: -1.0s;
    }

    .spinner .rect4 {
    -webkit-animation-delay: -0.9s;
    animation-delay: -0.9s;
    }

    .spinner .rect5 {
    -webkit-animation-delay: -0.8s;
    animation-delay: -0.8s;
    }

    @-webkit-keyframes sk-stretchdelay {
        0%, 40%, 100% { -webkit-transform: scaleY(0.4) }  
        20% { -webkit-transform: scaleY(1.0) }
    }

    @keyframes sk-stretchdelay {
        0%, 40%, 100% { 
            transform: scaleY(0.4);
            -webkit-transform: scaleY(0.4);
        }  20% { 
            transform: scaleY(1.0);
            -webkit-transform: scaleY(1.0);
        }
    }
    </style>

<?php }, "body" => function($opt) { ?> <!-- File body -->	
    <h2><?php $opt["lang"]("update"); ?></h2>

    <div class="card mb-1">
        <div class="card-header">
            <?php $opt["lang"]("installed_version"); ?>: <?php echo $opt["installed_version"] ?><br>
        </div>

        <div class="card-body stuff-to-hide-after-update">
            <?php if ($opt["installed_version"] == $opt["kernel_version"]) { ?>
                <p><?php $opt["lang"]("no_update_needed"); ?></p>
                <button class="btn btn-primary disabled"><?php $opt["lang"]("do_update"); ?></button>
            <?php } else { ?>
                <button class="btn btn-primary" id="button-update"><?php $opt["lang"]("do_update"); ?></button>
                <div class="spinner d-none" id="update-spinner">
                    <div class="rect1"></div>
                    <div class="rect2"></div>
                    <div class="rect3"></div>
                    <div class="rect4"></div>
                    <div class="rect5"></div>
                </div>
            <?php } ?>
        </div>

        <div id="update-log-viewer" class="card-body d-none">
            <div><?php $opt["lang"]("update_complete"); ?></div>
            <p id="update-log">

            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <?php $opt["lang"]("kernel_version"); ?>: <?php echo $opt["kernel_version"] ?>
        </div>

        <div class="card-body">
            <p>
                <?php $opt["lang"]("update_kernel"); ?>
            </p>
        </div>
    </div>

    <script>
        $(function() {
            $("#button-update").click(function() {
                $("#update-spinner").removeClass("d-none");
                Z.Request.action("update", {}, function(data) {
                    if (data.result == "success") {
                        $("#update-log-viewer").removeClass("d-none");
                        $("#update-log").html(data.log);
                        $(".stuff-to-hide-after-update").hide();
                    }
                });
            });
        });
    </script>

<?php }, "lang" => [
        "de_formal" => [
            "update" => "Update",
            "installed_version" => "Projekt Version",
            "kernel_version" => "Framework Version",
            "do_update" => "Updaten auf Framework Version",
            "no_update_needed" => "Es wird kein update benötigt.",
            "update_complete" => "Update Fertiggestellt!",
            "update_kernel" => "Um den Kern des Frameworks zu aktualisieren muss manuell eine neue Version heruntergeladen werden oder wenn er als Git submodule eingefügt wurde gepullt werden."
        ], 
        "en" => [
            "update" => "Update",
            "installed_version" => "Project version",
            "kernel_version" => "Framework version",
            "do_update" => "Update to framework version",
            "no_update_needed" => "No update is needed.",
            "update_complete" => "Update completed!",
            "update_kernel" => "Pull the submodule with git or download the new source manually to update the framework."
        ]
    ]
];
?>