@section("head")
 <!-- File header -->
@endsection

@section("body")
 <!-- File body -->	

    <script>
        $(function() {
            $("#create-group").click(() => {
                if (confirm("Do you really want to create a new role?")) {
                    Z.Request.action("create", {}, function(response) {
                        window.location.href = "<?php echo $opt["root"]. "z/roles/" ?>" + response.roleId;
                    });
                }
            });
        });
    </script>

    <h2>Roles</h2>

    <div class="list-group">
      <?php foreach($opt["roles"] as $role) { ?>
        <a data-test="role-<?php echo $role["id"]; ?>" href="<?php echo $opt["root"]. "z/roles/" . $role["id"]; ?>" class="list-group-item list-group-item-action"><?php echo $role["name"]; ?></a>
      <?php } ?>
    </div>

    <button data-test="role-create" class="btn btn-primary mt-2" id="create-group">Create role</button>
@endsection
