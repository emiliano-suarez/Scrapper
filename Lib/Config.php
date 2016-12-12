<?php

    namespace Scrapper\Lib;

    class Lib_Config {

        private $_data = null;
        private $_sites = array();

        public function __construct($params = array())
        {
            require "Config/Config.php";
            $this->_data = $data;

            if (is_array($params['sites'])) {
                $this->registerSites($params['sites']);
            }
            else {
                $this->_sites = $this->get('sites');
            }
        }

        public function get($key)
        {
            $elements = explode('\.', $key);
            $value = $this->_data;
            foreach ($elements as $element) {
                if (isset($value[$element])) {
                    $value = $value[$element];
                }
                else {
                    return false;
                }
            }
            return $value;
        }

        public function getSites()
        {
            return $this->_sites;
        }

        private function registerSites($sites = array())
        {
            foreach ($sites as $site) {
                $key = 'sites.' . $site;
                if ($this->get($key)) {
                    $this->_sites[$site] = $this->get($key);
                    // Force 'enabled' property to 'true'
                    $this->_sites[$site]['enabled'] = true;
                }
            }
        }
    }
