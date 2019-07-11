<?php 

    class CvController {

        public static $permissionLevel = 0;
        
        private function toNull($str) {
            return (!isset($str) || empty($str) || $str == "" ? null : $str);
        }

        public function action_retrieve_company_data($req, $res) {
            $res->generateRest(
                $req->getModel("Company")->getInfo()
            );
        }
    
        public function action_personal_information($req, $res) {

            $langValue = $req->getParameters(0, 1);
            $langId = $req->getModel("General")->getLanguageByValue($langValue);
            $langId = $langId === null ? 0 : $langId;
            $personalInformation = $req->getModel("PersonalInformation")->getByEmployeeIdAndLanguageId($req->getRequestingUser()["id"], $langId);

            if ($req->getPost("Save", false) !== false) {

                $informationParams = [
                    $this->toNull($req->getPost("addr_country", false)),
                    $this->toNull($req->getPost("addr_state", false)),
                    $this->toNull($req->getPost("addr_city", false)),
                    $this->toNull($req->getPost("addr_zip", false)),
                    $this->toNull($req->getPost("addr_street", false)),
                    $this->toNull($req->getPost("addr_street_number", false)),
                    $this->toNull($req->getPost("email", false)),
                    $this->toNull($req->getPost("web", false)),
                    $this->toNull($req->getPost("tel", false)),
                    $this->toNull($req->getPost("mobil", false)),
                    $this->toNull($req->getPost("fax", false)),
                    $this->toNull($req->getPost("position", false)),
                    $this->toNull($req->getPost("nationality", false)),
                    $this->toNull($req->getPost("birthdate", false))
                ];

                if (!$req->getModel("PersonalInformation")->checkIfExistsByEmployeeIdAndLanguageId($req->getRequestingUser()["id"], $langId)) {                    
                    $req->getModel("PersonalInformation")->addByEmployeeIdAndLanguageId(
                        $langId,
                        $req->getRequestingUser()["id"],
                        ...$informationParams
                    );
                } else {
                    $req->getModel("PersonalInformation")->editByIdAndLanguageId(
                        $personalInformation["id"],
                        $langId,
                        ...$informationParams
                    );
                }

                $success = true;

                foreach($req->getPost("history", []) as $professionalHistory) {

                    if ($professionalHistory["change"] == "remove") {
                        $req->getModel("ProfessionalHistory")->deleteById($personalInformation["id"], $professionalHistory["id"]);
                    } else if ($professionalHistory["change"] == "edit") {
                        $req->getModel("ProfessionalHistory")->editById($personalInformation["id"], $professionalHistory["id"], $this->toNull($professionalHistory["start"]), $this->toNull($professionalHistory["end"]), $professionalHistory["title"], $this->toNull($professionalHistory["position"]));
                    } else if ($professionalHistory["change"] == "add") {
                        $req->getModel("ProfessionalHistory")->add($personalInformation["id"], $this->toNull($professionalHistory["start"]), $this->toNull($professionalHistory["end"]), $professionalHistory["title"], $this->toNull($professionalHistory["position"]));
                    } else {
                        $success = false;
                    }

                }

                foreach($req->getPost("education", []) as $education) {

                    if ($education["change"] == "remove") {
                        $req->getModel("Education")->deleteById($personalInformation["id"], $education["id"]);
                    } else if ($education["change"] == "edit") {
                        $req->getModel("Education")->editById($personalInformation["id"], $education["id"], $this->toNull($education["start"]), $education["graduation"], $education["title"], $this->toNull($education["description"]));
                    } else if ($education["change"] == "add") {
                        $req->getModel("Education")->add($personalInformation["id"], $this->toNull($education["start"]), $education["graduation"], $education["title"], $this->toNull($education["description"]));
                    } else {
                        $success = false;
                    }
                    
                }
                
                $res->generateRest([
                    "result" => ($success ? "success" : "error")
                ]);

            }

            $res->render("cv_personal_information.php", [
                "title" => "CV - Skill-DB ACOPA",
                "personal_information" => $personalInformation,
                "first_name" => $req->getRequestingUser()["firstName"],
                "name" => $req->getRequestingUser()["name"],
                "countries" => $req->getModel("General")->getCountryList(),
                "professional_history" => $req->getModel("ProfessionalHistory")->getByPersonalInformationId($personalInformation["id"]),
                "education" => $req->getModel("Education")->getByPersonalInformationId($personalInformation["id"]),
                "ref_save" => $req->getPost("Save", false) !== false,
                "languages" => $req->getModel("General")->getLanguageList(),
                "selected_lang" => $langValue
            ]);
        }

        public function action_references($req, $res) {

            $langValue = $req->getParameters(0, 1);
            $langId = $req->getModel("General")->getLanguageByValue($langValue);
            $langId = $langId === null ? 0 : $langId;

            if ($req->getPost("Save", false) !== false) {

                $success = true;

                foreach($_POST["references"] as $reference) {

                    if ($reference["change"] == "remove") {
                        $req->getModel("Reference")->deleteById($reference["id"]);
                    } else if ($reference["change"] == "edit") {
                        $req->getModel("Reference")->editById($req->getRequestingUser()["id"], $this->toNull($reference["title"]), $this->toNull($reference["description"]), $this->toNull($reference["short_description"]), $this->toNull($reference["position"]), $this->toNull($reference["client"]), $this->toNull($reference["start"]), $this->toNull($reference["end"]), $reference["skillId"], $reference["id"]);
                    } else if ($reference["change"] == "add") {
                        $req->getModel("Reference")->add($req->getRequestingUser()["id"], $langId, $this->toNull($reference["title"]), $this->toNull($reference["description"]), $this->toNull($reference["short_description"]), $this->toNull($reference["position"]), $this->toNull($reference["client"]), $this->toNull($reference["start"]), $this->toNull($reference["end"]), $reference["skillId"]);
                    } else {
                        $success = false;
                    }
                    
                }

                $res->generateRest([
                    "result" => ($success ? "success" : "error")
                ]);

            }

            $res->render("cv_references.php", [
                "title" => "CV References - Skill-DB ACOPA",
                "references" => $req->getModel("Reference")->getByEmployeeIdAndLanguageId($req->getRequestingUser()["id"], $langId),
                "skill_categories" => $req->getModel("Skill")->getCategories(),
                "skill_list" => $req->getModel("Skill")->getOccurrences(),
                "languages" => $req->getModel("General")->getLanguageList(),
                "selected_lang" => $langValue
            ]);
        }

        public function action_view($req, $res) {
            $res->renderCV(
                $req->getRequestingUser()["id"],
                $req->getParameters(0, 1)
            );
        }

        public function action_portrait($req, $res) {

            if ($req->getParameters(0, 1, "upload")) {

                $upload = $req->upload();
                $uploadResult = $upload->image(
                    $_FILES["pp"],
                    $req->getBooterSettings("uploadFolder"),
                    FILE_SIZE_20MB
                );

                if ($uploadResult === UPLOAD_SUCCESS) {

                    //cropping
                    $x = round($req->getPost("x1"));
                    $y = round($req->getPost("y1"));
                    $width = round(abs($req->getPost("x1") - $req->getPost("x2")));
                    $height = round(abs($req->getPost("y1") - $req->getPost("y2")));
                    
                    $dst_image = imagecreatetruecolor($width, $height);
                    
                    if ($upload->extension == "png") { 
                        $src_image = imagecreatefrompng($upload->filePath);
                    } elseif ($upload->extension == "gif") {
                        $src_image = imagecreatefromgif($upload->filePath);
                    } else {
                        $src_image = imagecreatefromjpeg($upload->filePath);
                    }
                    
                    imagecopyresampled($dst_image, $src_image, 0, 0, $x, $y, $width, $height, $width, $height);
                    unlink($upload->filePath);
                    imagejpeg($dst_image, $upload->filePath);

                    $req->getModel("CV")->updateProfilePicture($req->getRequestingUser()["id"], $upload->fileId);
                }

                $result = array(
                    "result" => ($uploadResult === UPLOAD_SUCCESS ? "success" : "error")
                );

                if ($uploadResult === UPLOAD_SUCCESS ) {
                    $result["ref"] = $upload->ref;
                    $result["type"] = $upload->extension;
                    $result["fileId"] = $upload->fileId;
                }

                $res->generateRest($result);
                
            } else {
                $pp = $req->getModel("CV")->getProfilePictureByEmployeeId($req->getRequestingUser()["id"])[0];
                $res->render("cv_portrait.php", [
                    "title" => "Profile Picture - Skill-DB ACOPA",
                    "pp" => "/".$req->getBooterSettings("rootDirectory").$req->getBooterSettings("uploadFolder").$pp["reference"].".".$pp["extension"],
                    "ref_save" => $req->getParameters(0, 1, "save")
                ]);
            }
        }

        public function action_publish($req, $res) {

            if ($req->getParameters(0, 1, "generate")) {
                $req->getModel("CV")->addPublishedRefsByEmployeeId($req->getRequestingUser()["id"], $req->getPost("comment", ""), $req->getPost("language_id", "0"), $req->getModel("General")->getUniqueRef());
            } else if ($req->getParameters(0, 1, "delete")) {
                $req->getModel("CV")->removePublishedRefsId($req->getPost("id", null), $req->getRequestingUser()["id"]);
            } else {
                $res->render("cv_publish.php", [
                    "title" => "Publish CV - Skill-DB ACOPA",
                    "list" => $req->getModel("CV")->getPublishedRefsByEmployeeId($req->getRequestingUser()["id"]),
                    "base_url" => $req->getBooterSettings("host")."/".$req->getBooterSettings("rootDirectory")."public/cv/",
                    "languages" => $req->getModel("General")->getLanguageList()
                ]);
            }
            
        }

	}

?>