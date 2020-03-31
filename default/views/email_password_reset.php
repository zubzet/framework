<?php return [ "head" => function() { ?>

<?php }, "body" => function($opt) { ?>

    <h2>The Password Reset worked!</h2>
    <a href="<?php echo $opt["reset_link"]; ?>">
        Click this link to change your password!
    </a>
    <br>
    Or click here: <?php echo $opt["reset_link"]; ?>

<?php }]; ?>