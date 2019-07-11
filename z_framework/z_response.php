<?php 
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    $opt = [];

    class Response extends RequestResponseHandler {
                
        /**
         * Shows a document to the user
         * @param string $document Path to the view
         * @param string $params assosiative array with values to replace in the view
         */
        public function render($document, $opt = [], $layout = "layout/default") {

            $viewPath = $this->booter->getViewPath($document);

            if ($viewPath !== false) {

                //Set default parameter values
                $opt["root"] = $this->booter->rootFolder;
                if (!isset($opt["title"])) $opt["title"] = "Your Website";

                //logged in user information
                $opt["user"] = $this->booter->user;

                $opt["layout_essentials"] = function($opt) {
                    include "layout_essentials.php";
                };

                $userLang = "en";
                if (isset($opt["overwrite_lang"])) {
                    $userLang = $opt["overwrite_lang"];
                } else {
                    $userLang = $this->booter->user->language["value"];
                }
                $userLang = strtolower($userLang);

                $opt["layout_lang"] = $userLang;

                //Log view
                $catId = $this->booter->getModel("z_general")->getLogCategoryIdByName("view");
                $user = $this->booter->user->userId;
                $this->booter->getModel("z_general")->logAction($catId, "URL viewed (User ID: ".$user." ,URL: ".$_SERVER['REQUEST_URI'].")", $document);

                //Load the document
                include($viewPath);

                global $langStorage;
                $langStorage = array();

                if (function_exists("getLangArray") || isset($getLangArray)) {
                    $arr = isset($getLangArray) ? $getLangArray() : getlangArray();
                    
                    foreach($arr["en"] as $key => $val) {
                        if (isset($arr[$userLang][$key])) {
                            $langStorage[strtolower($key)] = $arr[$userLang][$key];
                        } else {
                            $langStorage[strtolower($key)] = $arr["en"][$key];
                        }
                    }
                }
                
                //Load the head and other standard layout
                include($this->booter->getViewPath($layout));

                if (function_exists("getLangArrayLayout") || isset($getLangArrayLayout)) {
                    $arr = isset($getLangArrayLayout) ? $getLangArrayLayout() : getlangArrayLayout();
                    
                    foreach($arr["en"] as $key => $val) {
                        if (isset($arr[$userLang][$key])) {
                            $langStorage[strtolower($key)] = $arr[$userLang][$key];
                        } else {
                            $langStorage[strtolower($key)] = $arr["en"][$key];
                        }
                    }
                }

                $opt["lang"] = function($key, $echo = true) {
                    global $langStorage;
                    $out = "";
                    if (isset($langStorage[$key])) {
                        $out = $langStorage[$key];
                    } else {
                        $out = $key;
                    }
                    if ($echo) {
                        echo $out;
                    }
                    return $out;
                };

                layout($opt, function($opt) { body($opt); }, function($opt) { head($opt); });
                
            } else {
                $this->reroute(["error", "404"]);
            }
        }

        public function renderPDF($document, $opt, $name = "CV.pdf", $dlOpt = "I", $pdfOptions = ['P', 'A4', 'en', true, 'UTF-8', array(20, 20, 20, 5)]) {

            // Library laden
            require_once('libs/vendor/autoload.php');

            //PDF obj
            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf(...$pdfOptions);
            
            //Set custom font
            //Todo: $html2pdf->setDefaultFont("Proxima_Nova");

            //HTML Account
            require_once($this->getZViews()."/$document");
            ob_start();
            layout($opt);
            $html = ob_get_clean();

            //export the PDF
            $html2pdf->writeHTML($html);
            $html2pdf->output($name, $dlOpt);

        }

        /**
         * Sends a simple text. Use only for debug reasons!
         * @param string $text
         */
        public function send($text) {
            echo $text;
        }

        /**
         * Reroutes to another action
         */
        public function reroute($path = []) {
            $this->booter->executePath($path);
        }

        /**
         * Reroutes at the users client
         */
        public function rerouteUrl($url = "", $root = null) {
            if ($root === null) $root = $this->booter->rootFolder;
            header("location: ".$root.$url);
            exit;
        }

        /**
         * Sets an cookie
         * @param any See: setcookie
         */
        public function setCookie() {
            setcookie(...func_get_args());
        }

        public function getBooterSettings($key = null) {
            return $key !== null ? $this->booter->settings[$key] : $this->booter->settings;
        }

        public function unsetCookie($name, $path = "/") {
            unset($_COOKIE[$name]);
            setcookie($name, '', time() - 3600, $path);
        }

        private function getNewRest($payload) {
            require_once $this->booter->z_framework_root.'z_rest.php';
            return new Rest($payload, $this->booter->urlParts);
        }

        function generateRest($payload, $die = true) {
            if (@$payload["result"] == "error") $this->generateRestError("ergc", getCaller(1));
            $this->getNewRest($payload)->execute($die);
        }

        function generateRestError($code, $message) {
            $model = $this->booter->getModel("z_general");
            $model->logAction($model->getLogCategoryIdByName("resterror"), "Rest error (Code: $code): $message", $code);
            $this->getNewRest([$code => $message])->ShowError($code, $message);
        }

        function sendEmail($to, $subject, $document, $lang = "en", $options = [], $layout = "email") {

            //Import the email template
            $template = $this->getZViews() . "layout/".$layout.".php";
            if (!file_exists($template)) return false;
    
            //Overwrite the language
            $lang = strtolower($lang);
            $options["overwrite_lang"] = $lang;
            
            if(is_array($subject)) {
                foreach ($subject as $key => $val) {
                    $subject[strtolower($key)] = $val;
                }
                if(isset($subject[$lang])) {
                    $subject = $subject[$lang];
                } else {
                    $subject = $subject["en"];
                }
            }
            $subject = "=?utf-8?b?".base64_encode($subject)."?=";
            
            $options["application_root"] = $this->getBooterSettings("host") . $this->booter->rootFolder;

            //Render the email template
            ob_start();
            $this->render($document, $options, $layout);
            $content = ob_get_clean();

            //Generate the headers
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: SKDB <".$this->booter->dedicated_mail.">\r\n";
            $headers .= "X-Mailer: PHP ". phpversion();

            //Send the mail
            return mail($to, $subject, $content, $headers);
        }

        function sendEmailToUser($userId, $subject, $document, $options = [], $layout = "email") {
            $target = $this->booter->getModel("z_user")->getUserById($userId);
            $language = $this->booter->getModel("z_general")->getLanguageById($target["languageId"])["value"];
            $this->sendEmail($target["email"], $subject, $document, $language, $options, $layout);
        }

        function loginAs($userId, $user_exec = null) {
            if($user_exec === null) $user_exec = $userId;
            $token = $this->booter->getModel("z_login", $this->booter->z_framework_root)->createLoginToken($userId, $user_exec);
            $this->setCookie("skdb_login_token", $token, time() + ($this->booter->settings["loginTimeoutSeconds"]), "/");

            if ($userId == $user_exec) {
                $this->booter->getModel("z_general")->logAction($this->booter->getModel("z_general")->getLogCategoryIdByName("login"), "User $user_exec logged in as $userId", $user_exec);
            } else {
                $this->booter->getModel("z_general")->logAction($this->booter->getModel("z_general")->getLogCategoryIdByName("loginas"), "User $user_exec logged in.", $user_exec);
            }
        }

        /**
         * Generates a generic error
         */
        function error() {
            $this->generateRest(["result" => "error"]);
        }

        /**
         * Sends an error array generated by validateForm() from Request. Exit
         * @param Array $errors The error array.
         */
        function formErrors($errors) {
            $this->generateRest(["result" => "formErrors", "formErrors" => array_merge(...func_get_args())]);
        }

        /**
         * Sends a success message to the client. Exit
         */
        function success() {
            $this->generateRest(["result" => "success"]);
        }

        /**
         * Logs the user out
         */
        function logout() {
            $user = $this->booter->user;
            if ($user->isLoggedIn) {
                $this->booter->getModel("z_general")->logActionByCategory("logout", "User logged out (" . $user->fields["email"] . ")", $user->fields["email"]);
                $this->unsetCookie("skdb_login_token");
                $this->rerouteUrl();
            }
        }

        /**
         * Updates a database row by a user filled form
         */
        function updateDatabase($table, $pkField, $pkType, $pkValue, $validationResult) {
            $db = $this->booter->z_db;
            $vals = [];
            $sql = "UPDATE $table SET";
            $types = "";

            for ($i = 0; $i < count($validationResult->fields) - 1; $i++) {
                $field = $validationResult->fields[$i];
                $sql .= " ". $field->dbField . " = ?, ";
                $types .= $field->dataType;
                $vals[] = $field->value;
            }

            $field = $validationResult->fields[$i];
            $sql .= " ". $field->dbField . " = ?";
            $types .= $field->dataType;
            $vals[] = $field->value;
            
            $sql .= " WHERE $pkField = ?;";
            $types .= $pkType;
            $vals[] = $pkValue;

            $db->exec($sql, $types, ...$vals);
        }

        /**
         * Executes a "Create Edit Delete"
         * @param String $table The name of the affected table in the database
         * @param FormResult $validationResult the result of a validated CED
         * @param Array $fix Fixed values. For example fix user id not set by the client
         */
        function doCED($table, $validationResult, $fix = []) {
            if ($validationResult->doNothing) return;

            $db = $this->booter->z_db;
            $name = $validationResult->name;

            foreach ($_POST[$name] as $item) {
                $z = $item["Z"];

                if ($z == "create") {
                    $types = "";
                    $fields = [];
                    $values = [];
                    $sqlValues = [];
                    foreach ($validationResult->fields as $field) {
                        $types .= $field->dataType;
                        $fields[] = $field->name;
                        $sqlValues[] = "?";
                        $values[] = $item[$field->name];
                    }
                    foreach ($fix as $k => $f) {
                        $sqlValues[] = "?";
                        $values[] = $f;
                        $fields[] = $k;
                        $types.="s";
                    }

                    $fields = implode(",", $fields);
                    $sqlValues = implode(",", $sqlValues);

                    $sql = "INSERT INTO $table ($fields) VALUES ($sqlValues)";
                    $db->exec($sql, $types, ...$values);
                } else if ($z == "edit") {
                    $types = "";
                    $values = [];
                    $dbId = $item["dbId"];
                    if (!isset($dbId)) {
                        $this->error();
                    }

                    $sql = "UPDATE $table SET";
                    for ($i = 0; $i < count($validationResult->fields); $i++) {
                        $field = $validationResult->fields[$i];

                        $types .= $field->dataType;
                        $values[] = $item[$field->name];
                        $sql .= " " . $field->name . " = ?";

                        if ($i < (count($validationResult->fields) - 1)) {
                            $sql .= ",";
                        }
                    }

                    $types .= "i";
                    $values[] = $dbId;
                    $sql .= " WHERE id = ?";
                    $db->exec($sql, $types, ...$values);
                } else if ($z == "delete") {
                    $sql = "UPDATE $table SET active = 0 WHERE id = ?";
                    $db->exec($sql, "i", $item["dbId"]);
                } else {
                    $this->error();
                }
            }

        }
    }

?>