<?php

    namespace Scrapper\Parser;
//    use Scrapper\Lib as Lib;

    class Parser_Common implements Parser_Interface {

        private $config = null;

        // public function __construct(Lib\Config $config)
        public function __construct()
        {
            // $this->config = $config;
            // echo "Parser_Common class >>>>\n";
        }

        public function run()
        {
            echo "Parser :: Common\n";
        }
    }
