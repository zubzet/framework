<?php

    class z_upload {

        public $ref;
        public $mime;
        public $srcName;
        public $extension;
        public $size;
        public $filePath;  
        public $fileId;

        private $req;
        function __construct($req) {
            $this->req = $req;
        }

        public function upload($file, $uploadDir, $maxSize, $typeArray) {

            $ref = $this->req->getModel("General")->getUniqueRef();

            $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $target_file = $uploadDir . $ref . "." . $extension;
            
            if (!in_array($extension, $typeArray)) return UPLOAD_ERROR_WRONG_TYPE;
            if ($file["size"] > $maxSize) return UPLOAD_ERROR_TOO_BIG;
            if (move_uploaded_file($file["tmp_name"], $target_file) === false) return UPLOAD_ERROR_NOT_MOVED;
            
            $this->ref = $ref;
            $this->mime = $file["type"];
            $this->srcName = basename($file["name"]);
            $this->extension = $extension;
            $this->size = $file["size"];
            $this->filePath = $target_file;  
            $this->fileId = $this->req->getModel("z_file", $this->req->getZRoot())->add($this->ref, $this->mime, $this->srcName, $this->extension, $this->size);

            return UPLOAD_SUCCESS;

        }

        public function image($file, $uploadDir, $maxSize = FILE_SIZE_2MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["jpg", "jpeg", "gif", "png"]
            );
        }

        public function video($file, $uploadDir, $maxSize = FILE_SIZE_100MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["mp4", "m4a", "m4v", "mov", "3gp", "ogg", "webm", "flv"]
            );
        }

        public function audio($file, $uploadDir, $maxSize = FILE_SIZE_2MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["mp3", "wav", "wma", "ogg", "m4a"]
            );
        }

    }

?>