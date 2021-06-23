<?php 

    class UploadController extends z_controller {

        public function action_index($req, $res) {
            $upload = $res->upload();
            if ($upload->upload($_FILES["file"], "uploads/", FILE_SIZE_100MB, ["txt", "jpg", "png"])) {
                $res->error();
            } else {
                $res->generateRest([
                    "result" => "success",
                    "fileId" => $upload->ref
                ]);
            }
        }
    }
?>
