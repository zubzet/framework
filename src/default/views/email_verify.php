<?php return [ "head" => function() {}, "body" => function($opt) { ?>	
    <h2>Thank you for signing up!</h2>
    <a href="<?php echo $opt["url"]; ?>">Click this link to verify your email!</a><br> Or click here: <?php echo $opt["url"]; ?>
<?php }]; ?>