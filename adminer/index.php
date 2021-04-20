<?php

    function adminer_object() {
        // Autoloader
            foreach (glob(__DIR__."/plugins/*.php") as $filename) {
            include_once "$filename";
        }

        class AdminerCustomization extends AdminerPlugin {
            public $host;
            public $username;
            public $password;
            public $database;

            public function name() {
                return '';
            }
            
            public function credentials() {
                return [
                    $this->host, 
                    $this->username, 
                    $this->password
                ];
            }

            public function login($login, $password) {
                return true;
            }
    
            public function database() {
                return $this->database;
            }

            public function headers() {
                header_remove("Content-Security-Policy");
                header("X-Frame-Options: SameOrigin");
            }
        }
        
        $adminer = new AdminerCustomization([
            new AdminerVersionNoVerify()
        ]);

        $adminer->host = $GLOBALS["credentials"]["host"];
        $adminer->username = $GLOBALS["credentials"]["username"];
        $adminer->password = $GLOBALS["credentials"]["password"];
        $adminer->database = $GLOBALS["credentials"]["database"];

        return $adminer;
    }

?>

<script type="text/javascript">
    var clicked = false;
    addEventListener('load', function () {
        if(!clicked && !window.location.href.includes("username")) {
            document.querySelector("input[type=submit]").click();
            clicked = true;
        }
    });
</script>

<?php 
    chdir(__DIR__);
    require_once __DIR__."/core.php"; 
?>