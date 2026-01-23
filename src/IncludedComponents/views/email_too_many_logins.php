<?php return [ "head" => function() {}, "body" => function($opt) { ?>	
    <p>
        Someone has tried multiple times to login into 
        your account with a wrong password. If that was 
        not you, make sure to have a secure and strong 
        password.
    </p>
    <p>
        Date: 
        <?= $opt["date"] ?>
    </p>
    <p>
        IP Address: <?= $opt["ip"]; ?>
    </p>
<?php }]; ?>