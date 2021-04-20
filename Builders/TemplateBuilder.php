<?php

    class TemplateBuilder {
        private $template;
        private $workingDir;
        public function __construct(string $workingDir, string $templateFile) {
            $this->workingDir = $workingDir;
            $this->template = file_get_contents(__DIR__."/templates/$templateFile");
        }

        private string $name;
        public function setName($name) {
            $name = ucfirst($name);
            $this->replace("name", $name);
            $this->name = $name;
        }

        public function replace(string $from, string $to) {
            $from = "%%$from%%";
            $this->template = str_replace($from, $to, $this->template);
        }

        public function saveTo(string $path, string $suffix = "") {
            $path = $this->workingDir.$path.$this->name.$suffix.".php";
            if(file_exists($path)) {
                Console::error(
                    "Component with the name {{light_blue}}{$this->name}{{default}} already exists.", 
                    true
                );
            }
            file_put_contents($path, $this->template);
        }
    }

?>