@extends($layout)

@section("head")
 <!-- File header -->
@endsection

@section("content")
 <!-- File body -->	
    <h2><?= e(__("admin.user_select.title")) ?></h2>

    <div class="list-group">
      <?php foreach($opt["users"] as $user) { ?>
        <a data-test="user" href="<?php echo $opt["root"]. "z/edit_user/" . $user["id"]; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
          <small class="mr-1">
            <code>[<?= e($user["id"]); ?>]</code>
          </small>

          <?php if(is_null($user["email"])) { ?>
            <i><?= e(__("admin.user_select.no_email")) ?></i>
          <?php } else { ?>
            <?= e($user["email"]); ?>
          <?php } ?>
        </a>
      <?php } ?>
    </div>
@endsection
