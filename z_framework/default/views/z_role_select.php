<?php function head($opt) { ?> <!-- File header -->

<?php } function body($opt) { ?> <!-- File body -->	

    <script>
        $(function() {
            $("#create-group").click(() => {
                Z.Request.action("create", {}, function(response) {
                    window.location.href = "<?php echo $opt["root"]. "z/roles/" ?>" + response.roleId;
                });
            });
        });
    </script>

    <h2><?php $opt["lang"]("edit_role"); ?></h2>

    <div class="list-group">
      <?php foreach($opt["roles"] as $role) { ?>
        <a href="<?php echo $opt["root"]. "z/roles/" . $role["id"]; ?>" class="list-group-item list-group-item-action"><?php echo $role["name"]; ?></a>
      <?php } ?>
    </div>

    <button class="btn btn-primary mt-2" id="create-group"><?php $opt["lang"]("create_role"); ?></button>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "edit_role" => "Rollen",
            "create_role" => "Rolle erstellen"
        ], 
        "en" => [
            "edit_role" => "Roles",
            "create_role" => "Create role"
        ]
    ];
}
?>