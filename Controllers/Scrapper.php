<?php

    namespace Scrapper\Controllers;
    use Scrapper\Lib as Lib;
    use Scrapper\Models as Models;

    class Controllers_Scrapper {

        private $_config = null;
        private $_sites = array();

        public function __construct(Lib\Lib_Config $config)
        {
            $this->_config = $config;
            $this->registerSites($this->_config->getSites());
        }

        private function registerSites($sites)
        {
            foreach ($sites as $site) {
                $this->_sites[] = new Models\Models_Site($site['name'],
                                             $site['ParserClass'],
                                             $site['enabled']
                                            );
            }
        }

        public function run()
        {
            foreach ($this->_sites as $site) {
                if ($site->isEnabled()) {
                    echo "Site: ". $site->getName() . "\n";
                    $site->getParser()->run();
                }
            }
        }
    }
