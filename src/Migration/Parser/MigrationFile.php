<?php
    namespace ZubZet\Framework\Migration\Parser;

    use DateTime;

    class MigrationFile {

        public string $filename;
        public DateTime $date;
        public string $version;
        public string $name;

        public function __construct(string $filename, DateTime $date, string $version, string $name) {
            $this->filename = $filename;
            $this->date = $date;
            $this->version = $version;
            $this->name = $name;
        }

        public $sqlBuffer = [];
        public $skip = false;
        public $manual = false;
        public $environment = "default";

        public function extractData() {
            $extension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
            $classname = "ZubZet\Framework\Migration\Parser\Migration" . strtoupper($extension);

            (new $classname())->extractInformation($this->filename, $this->sqlBuffer, $this->skip, $this->environment, $this->manual);
        }

    }