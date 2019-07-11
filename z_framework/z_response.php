<?php 
    /**
     * Route handling system documentation:
     * Every action takes two parameters.
     * Request => used to get incoming and session stuff
     * Response => used to handle outgoing stuff
     */

    $opt = [];

    class Response {

        private $booter;
        private $z_views;

        public function __construct($booter) {
            $this->booter = $booter;
            $this->z_views = $this->booter->z_views;
        }

        public function getZViews() {
            return $this->booter->z_views;
        }

        public function getZRoot() {
            return $this->booter->z_framework_root; 
        }
                
        /**
         * Shows a document to the user
         * @param string $document Path to the view
         * @param string $params assosiative array with values to replace in the view
         */
        public function render($document, $opt = [], $layout = "default") {

            if (file_exists($this->z_views.$document)) {

                //Set default parameter values
                $opt["root"] = $this->booter->rootFolder;
                if (!isset($opt["title"])) $opt["title"] = "Skill-DB ACOPA";

                //logged in user information
                $opt["user"] = $this->booter->rqclient;

                $userLang = "en";
                if (isset($opt["overwrite_lang"])) {
                    $userLang = $opt["overwrite_lang"];
                } else {
                    if (isset($this->booter->rqclient["languageId"])) {
                        $userLang = $this->booter->getModel("General")->getLanguageById($this->booter->rqclient["languageId"])["value"];
                    }
                }
                $userLang = strtolower($userLang);

                $opt["layout_lang"] = $userLang;

                //Log view
                $catId = $this->booter->getModel("General")->getLogCategoryIdByName("view");
                $user = isset($opt["user"]["id"]) ? $opt["user"]["id"] : "annonymous";
                $this->booter->getModel("General")->logAction($catId, "URL viewed (User ID: ".$user." ,URL: ".$_SERVER['REQUEST_URI'].")", $document);

                //Load the document
                include($this->z_views.$document);

                if (function_exists("getLangArray") || isset($getLangArray)) {
                    $GLOBALS["lang"] = isset($getLangArray) ? $getLangArray() : getlangArray();
                    foreach($GLOBALS["lang"] as $key => $val) {
                        if (strtolower($key) == $key) continue;
                        unset($GLOBALS["lang"][$key]);
                        $GLOBALS["lang"][strtolower($key)] = $val;
                    }
                    $GLOBALS["userLangVal"] = $userLang;

                    //Translating the page
                    $opt["lang"] = function($key, $echo = true) {
                        if (!isset($GLOBALS["lang"][$GLOBALS["userLangVal"]])) $GLOBALS["userLangVal"] = "en";
                        if (isset($GLOBALS["lang"][$GLOBALS["userLangVal"]][$key])) {
                            if ($echo) echo $GLOBALS["lang"][$GLOBALS["userLangVal"]][$key];
                            else return $GLOBALS["lang"][$GLOBALS["userLangVal"]][$key];
                        } else {
                            if (isset($GLOBALS["lang"]["en"][$key])) {
                                if ($echo) echo $GLOBALS["lang"]["en"][$key];
                                else return $GLOBALS["lang"]["en"][$key];
                            } else {
                                if ($echo) echo $key;
                                else return $key;
                            }
                        }
                    };
                    
                }
                
                //Load the head and other standard layout
                include($this->z_views."layout/".$layout.".php");

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

        public function renderCV($userId, $langValue = "en", $langId = 0) {

            $langId = $langValue === null ? $langId : $this->booter->getModel("General")->getLanguageByValue($langValue);
            $langId = $langId === null ? 0 : $langId;

            $pp = $this->booter->getModel("CV")->getProfilePictureByEmployeeId($userId)[0];
            $pp_link = $this->getBooterSettings("uploadFolder").$pp["reference"].".".$pp["extension"];

            $this->booter->getModel("CV")->addGeneration($userId);
            $this->renderPDF("layout/cv_layout.php", [
                "references" => $this->booter->getModel("Reference")->getByEmployeeIdAndLanguageId($userId, $langId),
                "user_information" => $this->booter->getModel("Employee")->getMetaById($userId),
                "personal_information" => $this->booter->getModel("PersonalInformation")->getByEmployeeIdAndLanguageId($userId, $langId),
                "education" => $this->booter->getModel("Education")->getByPersonalInformationId($this->booter->getModel("PersonalInformation")->getByEmployeeIdAndLanguageId($userId, $langId)["id"]),
                "professional_history" => $this->booter->getModel("ProfessionalHistory")->getByPersonalInformationId($this->booter->getModel("PersonalInformation")->getByEmployeeIdAndLanguageId($userId, $langId)["id"]),
                "company_info" => $this->booter->getModel("Company")->getInfo(), //Could be wrong
                "profile_picture" => $pp_link
            ]);
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
            $model = $this->booter->getModel("general");
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

            //Send the mai
            return mail($to, $subject, $content, $headers);
        }

        function sendEmailToUser($userId, $subject, $document, $options = [], $layout = "email") {
            $target = $this->booter->getModel("z_login", $this->getZRoot())->getUserById($userId);
            $language = $this->booter->getModel("General")->getLanguageById($target["languageId"])["value"];
            $this->sendEmail($target["email"], $subject, $document, $language, $options, $layout);
        }

        function loginAs($employeeId, $employee_exec = null) {
            if($employee_exec === null) $employee_exec = $employeeId;
            $token = $this->booter->getModel("z_login", $this->booter->z_framework_root)->createLoginToken($employeeId, $employee_exec);
            $this->setCookie("skdb_login_token", $token, time() + ($this->booter->settings["loginTimeoutSeconds"]), "/");

            if ($employeeId == $employee_exec) {
                $this->booter->getModel("General")->logAction($this->booter->getModel("General")->getLogCategoryIdByName("login"), "User $employee_exec logged in as $employeeId", $employee_exec);
            } else {
                $this->booter->getModel("General")->logAction($this->booter->getModel("General")->getLogCategoryIdByName("loginas"), "User $employee_exec logged in.", $employee_exec);
            }
        }

    }

?>