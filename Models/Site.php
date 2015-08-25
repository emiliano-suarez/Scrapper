<?php

    namespace Scrapper\Models;

    class Models_Site {

        private $_name;
        private $_parserName;
        private $_enabled;
        private $_parser;

        public function __construct($name = '', $parserName, $enabled)
        {
            $this->_name = $name;
            $this->_parserName = $parserName;
            $this->_enabled = $enabled;
            $this->_parser = $this->getParserInstance($name);
        }

        public function getName()
        {
            return $this->_name;
        }

        public function getParserName()
        {
            return $this->_parserName;
        }

        public function isEnabled()
        {
            return $this->_enabled;
        }

        public function getParser()
        {
            return $this->_parser;
        }

        private function getParserInstance($siteName)
        {
            $parserName = 'Scrapper\\Parser\\' . $this->_parserName;
            return new $parserName($siteName);
        }
    }
