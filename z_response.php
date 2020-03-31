<?php 
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    /**
     * @var object $opt Holds options needed for rendering
     */
    $opt = [];

    /**
     * The response class handles functions used by controllers to respond to requests
     */
    class Response extends RequestResponseHandler {
        
        /**
         * Shows a document to the user
         * @param string $document Path to the view
         * @param string $opt assosiative array with values to replace in the view
         * @param string $layout The path to the layout to use
         */
        public function render($document, $opt = [], $layout = "layout/default_layout.php") {

            $viewPath = $this->booter->getViewPath($document);

            if ($viewPath !== false) {

                //Set default parameter values
                $opt["response"] = $this;
                $opt["request"] = $this->booter->req;
                $opt["root"] = $this->booter->rootFolder;
                $opt["host"] = $this->booter->host;

                if (!isset($opt["title"])) $opt["title"] = $this->getBooterSettings("pageName");

                //logged in user information
                $opt["user"] = $this->booter->user;

                include_once "layout_essentials.php";
                $opt["layout_essentials_body"] = function($opt) {
                    essentialsBody($opt);
                };
                $opt["layout_essentials_head"] = function($opt) {
                    essentialsHead($opt);
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

                $this->booter->getModel("z_general")->logAction($catId, "URL viewed (User ID: ".$user." ,URL: ".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "console").")", $document);

                //Load the document
                $view = include($viewPath);

                global $langStorage;
                $langStorage = array();

                $arr = isset($view["lang"]) ? $view["lang"] : [];
                if (!isset($arr["en"])) $arr["en"] = [];
                
                foreach($arr["en"] as $key => $val) {
                    if (isset($arr[$userLang][$key])) {
                        $langStorage[strtolower($key)] = $arr[$userLang][$key];
                    } else {
                        $langStorage[strtolower($key)] = $arr["en"][$key];
                    }
                }
        
                //Load the layout
                $layout = include($this->booter->getViewPath($layout));
                $arr = isset($layout["lang"]) ? $layout["lang"] : [];
                if (!isset($arr["en"])) $arr["en"] = [];

                //TODO: Document $arr["en"]
                foreach($arr["en"] as $key => $val) {
                    if (isset($arr[$userLang][$key])) {
                        $langStorage[strtolower($key)] = $arr[$userLang][$key];
                    } else {
                        $langStorage[strtolower($key)] = $arr["en"][$key];
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

                $opt["generateResourceLink"] = function($url, $root = true) {
                    $v = $this->getBooterSettings("assetVersion");
                    echo (($root ? $this->booter->rootFolder : "") . $url . "?v=" . (($v == "dev") ? time() : $v));
                };

                $opt["echo"] = function($val) {
                    echo nl2br(htmlspecialchars($val));
                };
                
                //Makes $body and $head optional
                if(!isset($view["body"])) $view["body"] = function(){};
                if(!isset($view["head"])) $view["head"] = function(){};
                
                $layout["layout"]($opt, $view["body"], $view["head"]);
            } else {
                $this->reroute(["error", "404"]);
            }
        }

        /**
         * Renders a PDF file
         * @param string $document Path to the view
         * @param array $opt Array of data to use by the view
         * @param string $name name of the output file
         * @param string $dlOpt Html2Pdf opts
         * @param array $pdfOptions PDF options (see Html2Pdf constructor)
         */
        public function renderPDF($document, $opt, $name = "CV.pdf", $dlOpt = "I", $pdfOptions = ['P', 'A4', 'en', true, 'UTF-8', array(20, 20, 20, 5)]) {
            // Library laden
            require_once('vendor/autoload.php');
            //PDF obj
            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf(...$pdfOptions);

            //HTML Account
            require_once($this->getZViews()."/$document");
            ob_start();
            layout($opt, null, null);
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
         * Sends a file to the user and forces him to show the file in the browser if possible. Useful for sending files the user has no access to.
         * @param string $path Path to the file.
         * @param string $filename Name to show at the client. Do not use the internal server path!
         * @param string $type Type of the file
         */
        public function showFile($path, $filename = "unkown", $type = "application/pdf") {
            $url = $path;
            $content = file_get_contents($url);
        
            header('Content-Type: ' . $type);
            header('Content-Length: ' . strlen($content));
            header('Content-Disposition: inline; filename="'. $filename. '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            ini_set('zlib.output_compression','0');
        
            die($content);
        }

        /**
         * Reroutes to another action
         * @param string[] $path Path to where to reroute to
         * @param bool $alias true if this reroute acts as an alias
         */
        public function reroute($path = [], $alias = false) {
            if(!$alias) {
                $this->booter->executePath($path);
            } else {
                $parts = array_values($this->booter->urlParts);
                foreach ($path as $i => $path_part) {
                    $parts[$i] = $path_part;
                }
                $this->booter->executePath($parts);
            }
        }

        /**
         * Reroutes at the users client
         * @param string $url
         * @param string $root
         */
        public function rerouteUrl($url = "", $root = null) {
            if ($root === null) $root = $this->booter->rootFolder;
            header("location: ".$root.$url);
            exit;
        }

        /**
         * Sets a cookie just like the standard PHP function. (Passthrough)
         * See: https://www.php.net/manual/en/function.setcookie.php
         * @param any $args See: setcookie
         */
        public function setCookie() {
            setcookie(...func_get_args());
        }

        /**
         * Removes a cookie at the client
         * @param string $name Name of the cookie
         * @param string $path Path of the server
         */
        public function unsetCookie($name, $path = "/") {
            unset($_COOKIE[$name]);
            setcookie($name, '', time() - 3600, $path);
        }

        /**
         * Creates an upload object that handles the rest of the upload.
         * @return z_upload A new instance of the z_upload class
         */
        public function upload() {
            require_once $this->getZRoot()."z_upload.php";
            return new z_upload($this);
        }

        /**
         * Gets a new rest object
         * @param object $payload data
         */
        private function getNewRest($payload) {
            require_once $this->booter->z_framework_root.'z_rest.php';
            return new Rest($payload, $this->booter->urlParts);
        }

        /**
         * Generates a rest object
         * @param object $payload data
         * @param bool $die
         */
        function generateRest($payload, $die = true) {
            //if (@$payload["result"] == "error") $this->generateRestError("ergc", getCaller(1));
            $this->getNewRest($payload)->execute($die);
        }

        /**
         * Generates a rest error object
         * @param string $code Code
         * @param string $message Error Message
         */
        function generateRestError($code, $message) {
            $model = $this->booter->getModel("z_general");
            $model->logAction($model->getLogCategoryIdByName("resterror"), "Rest error (Code: $code): $message", $code);
            $this->getNewRest([$code => $message])->ShowError($code, $message);
        }

        private $mailer;

        /**
         * Sends an email to an address
         * @param string $to Mail address
         * @param string $subject Subject of the mail
         * @param string $document View
         * @param string $lang Language identifier ("EN", "DE_Formal"...)
         * @param object $options Options to use in the view
         * @param string $layout Layout
         */
        function sendEmail($to, $subject, $document, $lang = "en", $options = [], $layout = "layout/mail_layout.php") {
            //Import the email template
            $template = $this->booter->getViewPath($layout);
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
            if (ob_get_contents()) ob_end_clean();

            $from = $this->getBooterSettings("mail_user");
            $sender = $this->getBooterSettings("pageName");

            require_once 'vendor/autoload.php';

            if(empty($this->mailer)) {
                $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            }
            $mail = $this->mailer;

            try {
                //Server settings
                // Set mailer to use SMTP
                $mail->SMTPDebug = 0;                                       
                $mail->isSMTP();                                            
                $mail->Host       = $this->getBooterSettings("mail_smtp");

                // Specify main and backup SMTP servers
                $mail->SMTPAuth   = true;
                $mail->Username   = $this->getBooterSettings("mail_user");  
                $mail->Password   = $this->getBooterSettings("mail_password");

                // Enable TLS encryption, `ssl` also accepted
                $mail->SMTPSecure = 'tls';      

                // TCP port to connect to
                $mail->Port       = isset($this->getBooterSettings()["mail_port"]) ? $this->getBooterSettings("mail_port") : 587;
            
                //Recipients
                $mail->setFrom($from, $this->getBooterSettings("pageName"));
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $content;
                $mail->AltBody = strip_tags(str_replace("<br>", "\n\r", $content));
            
                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }

        /**
         * Sends an email to a user
         * @param int $userId id of the target user
         * @param string $subject Subject of the mail
         * @param string $document View of the mail
         * @param object $options Options for use in the view
         * @param string $layout Layout to use
         */
        function sendEmailToUser($userId, $subject, $document, $options = [], $layout = "layout/mail_layout.php") {
            $target = $this->booter->getModel("z_user")->getUserById($userId);
            $langObj = $this->booter->getModel("z_general")->getLanguageById($target["languageId"]);
            $language = isset($langObj["value"]) ? $langObj["value"] : $req->getBooterSettings("anonymous_language");
            $this->sendEmail($target["email"], $subject, $document, $language, $options, $layout);
        }

        /**
         * Logs the current user in as someone else
         * @param int $userId id of the user to sudo in
         * @param int $user_exec id of the executing user
         */
        function loginAs($userId, $user_exec = null) {
            if($user_exec === null) $user_exec = $userId;
            $token = $this->booter->getModel("z_login", $this->booter->z_framework_root)->createLoginToken($userId, $user_exec);
            $this->setCookie("z_login_token", $token, time() + intval($this->booter->settings["loginTimeoutSeconds"]), "/");

            if ($userId == $user_exec) {
                $this->booter->getModel("z_general")->logAction($this->booter->getModel("z_general")->getLogCategoryIdByName("login"), "User $user_exec logged in as $userId", $user_exec);
            } else {
                $this->booter->getModel("z_general")->logAction($this->booter->getModel("z_general")->getLogCategoryIdByName("loginas"), "User $user_exec logged in.", $user_exec);
            }
        }

        /**
         * Generates a generic error
         * @param string $message An error message
         */
        function error($message = "") {
            $this->generateRest(["result" => "error", "message" => $message]);
        }

        /**
         * Sends an error array generated by validateForm() from Request. Exit
         * @param object[] $errors The error array.
         */
        function formErrors($errors) {
            $errors = array_filter(func_get_args(), function($var) { return is_array($var); });
            $this->generateRest(["result" => "formErrors", "formErrors" => array_merge(...$errors)]);
        }

        /**
         * Sends a success message to the client. Exit
         * @param mixed[] $playload An optional payload that will be added to the result
         */
        function success($playload = []) {
            $result = ["result" => "success"];
            $result = array_merge($result, $playload);
            $this->generateRest($result);
        }

        /**
         * Logs the user out
         */
        function logout() {
            $user = $this->booter->user;
            if ($user->isLoggedIn) {
                $this->booter->getModel("z_general")->logActionByCategory("logout", "User logged out (" . $user->fields["email"] . ")", $user->fields["email"]);
                $this->unsetCookie("z_login_token");
                $this->rerouteUrl();
            }
        }

        /**
         * Logs something
         * @param string $categoryName Name of the log category in the database
         * @param string $text Log Text
         * @param int $value Log Value
         */
        function log($categoryName, $text, $value) {
            $this->booter->getModel("z_general")->logActionByCategory($categoryName, $text, $value);
        }

        /**
         * Updates a database row by a user filled form
         * @param string $table Tablename in the database
         * @param string $pkField Name of the field in the database of the primary key
         * @param string $pkType Type of the primary field ("s"/"i"...)
         * @param string $pkValue Value of the primary key in the row to change
         * @param ValidationResult $validationResult Result of a validation
         */
        function updateDatabase($table, $pkField, $pkType, $pkValue, $validationResult) {
            $db = $this->booter->z_db;
            $vals = [];
            $sql = "UPDATE `$table` SET";
            $types = "";

            for ($i = 0; $i < count($validationResult->fields) - 1; $i++) {
                $field = $validationResult->fields[$i];
                $sql .= " `". $field->dbField . "` = ?, ";
                $types .= $field->dataType;
                $vals[] = $field->value;
            }

            $field = $validationResult->fields[$i];
            $sql .= " `". $field->dbField . "` = ?";
            $types .= $field->dataType;
            $vals[] = $field->value;
            
            $sql .= " WHERE `$pkField` = ?;";
            $types .= $pkType;
            $vals[] = $pkValue;

            $db->exec($sql, $types, ...$vals);
        }

        /**
         * Inserts a set into the database with data from a form
         * @param string $table Tablename in the database
         * @param ValidationResult $validationResult Result of a validation
         * @param array Some values to add to the database that were not in the Formresult
         */
        function insertDatabase($table, $validationResult, $fixed = []) {

            //First check for file uploads
            foreach ($validationResult->fields as $field) {
                if ($field->isFile) {
                    $upload = $this->upload();
                    if(!isset($_FILES[$field->name])) continue; //TODO: Might take required into account
                    //TODO: Should use the uploads folder
                    $uploadCode = $upload->upload($_FILES[$field->name], "uploads/", $field->fileMaxSize, $field->fileTypes);
                    if ($uploadCode) $this->error("Upload error: " . $uploadCode);
                    $field->value = $upload->fileId;
                }
            }

            //then do other stuff for normal database activity
            $db = $this->booter->z_db;
            $vals = [];

            $sqlParams = [];
            $sqlValues = [];

            $types = "";

            foreach ($fixed as $col => $val) {
                $sqlParams[] = "`$col`";
                $types .= "s";
                $sqlValues[] = "?";
                $vals[] = $val;
            }

            for ($i = 0; $i < count($validationResult->fields); $i++) {
                $field = $validationResult->fields[$i];
                $sqlParams[] = ("`" . $field->dbField . "`");
                $sqlValues[] = "?";
                $types .= $field->dataType;
                $vals[] = $field->value;
            }

            $sqlCmdParams = implode(",", $sqlParams);
            $sqlCmdValues = implode(",", $sqlValues);

            $sql = "INSERT INTO `$table` ($sqlCmdParams) VALUES ($sqlCmdValues)";
            $db->exec($sql, $types, ...$vals);
            
            return $db->getInsertId();
        }

        /**
         * Executes a "Create Edit Delete"
         * @param string $table The name of the affected table in the database
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