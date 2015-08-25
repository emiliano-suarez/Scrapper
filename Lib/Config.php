<?php

    namespace Scrapper\Lib;

    class Lib_Config {

        private $data = null;

        public function __construct()
        {
            require "Config/Config.php";
            $this->data = $data;
        }

        public function get($key)
        {
            $elements = split('\.', $key);
            $value = $this->data;
            foreach ($elements as $element) {
                $value = $value[$element];
            }
            return $value;
        }

        public function getSites()
        {
            return $this->get('sites');
        }
    }
