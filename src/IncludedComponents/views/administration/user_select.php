<?php 
/**
 * The user select view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	
    <h2>Edit User</h2>

    <div class="list-group">
      <?php foreach($opt["users"] as $user) { ?>
        <a data-test="user" href="<?php echo $opt["root"]. "z/edit_user/" . $user["id"]; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
          <small class="mr-1">
            <code>[<?= e($user["id"]); ?>]</code>
          </small>

          <?php if(is_null($user["email"])) { ?>
            <i>No email</i>
          <?php } else { ?>
            <?= e($user["email"]); ?>
          <?php } ?>
        </a>
      <?php } ?>
    </div>

<?php }];?>
