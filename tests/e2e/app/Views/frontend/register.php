<?php return [ 'body' => function($opt) { ?>
    <div id="login-error-label" data-test="login-error"></div>

    <input id="input-username" type="text" data-test="email">

    <input id="input-password" type="password" data-test="password">

    <input id="input-password-confirm" type="password" data-test="password-confirm">

    <button id="button-signup" data-test="submit">Submit</button>

    <script>
        document.getElementById("button-signup").addEventListener("click", () => {
            Z.Presets.Signup(
                "input-username", 
                "input-password", 
                "input-password-confirm", 
                "login-error-label", 
                "<?php echo $opt["root"]; ?>"
            );
        });
    </script>

    <div>
        <ul>
            <?php foreach($opt["users"] as $user) {?>
                <li>
                    <div data-test="user"><?= $user["email"] ?></div>
                    <div><?= $user["password"] ?></div>
                    <div><? $user["salt"] ?></div>
                </li>
             <?php } ?>
        </ul>
    </div>
<?php }]; ?>