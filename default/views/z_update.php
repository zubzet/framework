<?php 
/**
 * The user select view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

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
            "installed_version" => "Installierte Version",
            "kernel_version" => "Kernel Version",
            "do_update" => "Updaten auf Kernel Version",
            "no_update_needed" => "Es wird kein update benötigt.",
            "update_complete" => "Update Fertiggestellt!",
            "update_kernel" => "Um den Kern des Frameworks zu aktualisieren muss manuell eine neue Version heruntergeladen werden oder wenn er als Git submodule eingefügt wurde gepullt werden."
        ], 
        "en" => [
            "update" => "Update",
            "installed_version" => "Installed version",
            "kernel_version" => "Kernel version",
            "do_update" => "Update to kernel version",
            "no_update_needed" => "No update is needed.",
            "update_complete" => "Update completed!",
            "update_kernel" => "Pull the submodule with git or download the new source manually to update the kernel."
        ]
    ]
];
?>