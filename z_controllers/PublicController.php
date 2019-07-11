<?php 

    class PublicController {

        public static $permissionLevel = -1;

        public function action_cv($req, $res) {
            $ref = $req->getParameters(0, 1);
            $details = $req->getModel("CV")->getDetailsByRef($ref);
            $req->getModel("CV")->addLinkViewsById($details["id"]);

            $langValue = $req->getParameters(0, 1);
            $res->renderCV(
                $details["employeeId"],
                null,
                $details["languageId"]
            );
        }

    }

?>