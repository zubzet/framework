<?php

    namespace ZubZet\Framework\Form;

    use ZubZet\Framework\Message\Request;

    /**
     * Class that handles file uploads and checking types
     */
    class Upload {

        /** @var string $ref Reference */
        public $ref;

        /** @var string $mime MIME-Type */
        public $mime;

        /** @var string $srcName Source name of the uploading file */
        public $srcName;

        /** @var string $extension File extension of the uploading file */
        public $extension;

        /** @var int $size Size of the uploading file in bytes*/
        public $size;

        /** @var string $filePath Path to the uploading file */
        public $filePath;  

        /** @var int $fileId File Id, set after upload */
        public $fileId;

        /** @var Request $req Request object of the current request*/
        private $res;

       /**
        * Uploads a file to an upload folder
        *
        * This function will handle file uploads for you and make sure the type and the size are as defined. It also handles the movement to an upload folder. Be sure to change upload_max_filesize and post_max_size in you php.ini. It creates $uploadDir if missing and, on success, inserts a z_file row and fills ref, fileId and filePath; UPLOAD_SUCCESS is 0, so check errors with truthiness.
        *
        * @param array $file The $_FILES entry of the file. Example getFile("profile_picture")
        * @param string $uploadDir The path to which the file will be uploaded
        * @param int $maxSize The maximum size of the file in bytes (0 = no limit). Use the defined constants like FILE_SIZE_2MB
        * @param string[] $typeArray An array of allowed filetypes like ["jpg", "png"]
        * @return int The result of the upload. This could be something like UPLOAD_SUCCESS or UPLOAD_ERROR_TOO_BIG
        */
        public function upload($file, $uploadDir, $maxSize, $typeArray) {
            if (empty($file) || !isset($file["name"]) || $file === null) {
                return UPLOAD_ERROR_NO_FILE;
            }

            $ref = model("z_general")->getUniqueRef();

            $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $target_file = $uploadDir . $ref . "." . $extension;
            if (!empty($typeArray) && !in_array($extension, $typeArray)) {
                return UPLOAD_ERROR_WRONG_TYPE;
            }

            if ($maxSize != 0 && $file["size"] > $maxSize) {
                return UPLOAD_ERROR_TOO_BIG;
            }

            if (move_uploaded_file($file["tmp_name"], $target_file) === false) {
                return UPLOAD_ERROR_NOT_MOVED;
            }

            $this->ref = $ref;
            $this->mime = $file["type"];
            $this->srcName = basename($file["name"]);
            $this->extension = $extension;
            $this->size = $file["size"];
            $this->filePath = $target_file;
            $this->fileId = model("z_file")->add(
                $this->ref, 
                $this->mime, 
                $this->srcName, 
                $this->extension, 
                $this->size
            );

            return UPLOAD_SUCCESS;
        }

        /**
        * Uploads a file, which must be an image (jpg, jpeg, png or bmp)
        *
        * An implementation of the upload method with an image filter. This function will handle file uploads for you and make sure the type and the size are as defined. It also handles the movement to an upload folder. Be sure to change upload_max_filesize and post_max_size in you php.ini
        *
        * @param array $file The $_FILES entry of the file. Example getFile("profile_picture")
        * @param string $uploadDir The path to which the file will be uploaded
        * @param int $maxSize The maximum size of the file in bytes (0 = no limit). Use the defined constants like FILE_SIZE_2MB
        * @return int The result of the upload. This could be something like UPLOAD_SUCCESS or UPLOAD_ERROR_TOO_BIG
        */
        public function image($file, $uploadDir, $maxSize = FILE_SIZE_2MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["jpg", "jpeg", "png", "bmp"]
            );
        }

       /**
        * Uploads a file, which must be a video (mp4, m4a, m4v, mov, 3gp, ogg, webm, flv, mkv, avi or wmv)
        *
        * An implementation of the upload method with a video filter. This function will handle file uploads for you and make sure the type and the size are as defined. It also handles the movement to an upload folder. Be sure to change upload_max_filesize and post_max_size in you php.ini
        *
        * @param array $file The $_FILES entry of the file. Example getFile("recording")
        * @param string $uploadDir The path to which the file will be uploaded
        * @param int $maxSize The maximum size of the file in bytes (0 = no limit). Use the defined constants like FILE_SIZE_100MB
        * @return int The result of the upload. This could be something like UPLOAD_SUCCESS or UPLOAD_ERROR_TOO_BIG
        */
        public function video($file, $uploadDir, $maxSize = FILE_SIZE_100MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["mp4", "m4a", "m4v", "mov", "3gp", "ogg", "webm", "flv", "mkv", "avi", "wmv"]
            );
        }

       /**
        * Uploads a file, which must be an audio file (mp3, wav, wma, ogg, m4a, aiff or aac)
        *
        * An implementation of the upload method with an audio filter. This function will handle file uploads for you and make sure the type and the size are as defined. It also handles the movement to an upload folder. Be sure to change upload_max_filesize and post_max_size in you php.ini
        *
        * @param array $file The $_FILES entry of the file. Example getFile("voice_rec")
        * @param string $uploadDir The path to which the file will be uploaded
        * @param int $maxSize The maximum size of the file in bytes (0 = no limit). Use the defined constants like FILE_SIZE_10MB
        * @return int The result of the upload. This could be something like UPLOAD_SUCCESS or UPLOAD_ERROR_TOO_BIG
        */
        public function audio($file, $uploadDir, $maxSize = FILE_SIZE_10MB) {
            return $this->upload($file, $uploadDir, $maxSize, 
                ["mp3", "wav", "wma", "ogg", "m4a", "aiff", "aac"]
            );
        }

    }

?>