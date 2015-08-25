<?php

    namespace Scrapper\Parser;
    use Scrapper\Lib as Lib;
    use Scrapper\Models as Models;

    class Parser_AngelList implements Parser_Interface {

        private $_siteName;
        private $_config = null;
        private $_helper;
        private $_companyList = array();

        public function __construct($siteName = "")
        {
            $this->_siteName = $siteName;
            $this->_helper = new Lib\Lib_Helper();
        }

        public function run()
        {
            $this->getCompanies();
        }

        private function getCompanies()
        {
            $url = "https://angel.co/company_filters/search_data";
            $headers = array('X-Requested-With: XMLHttpRequest');
            
            for ($i = 1; $i <= 10; $i++) {
                echo "\nPage: " . $i . "\n";
                $fields = array(
                        'filter_data[stage]' => 'Acquired',
                        'sort' => 'signal',
                        'page' => $i,
                );
                $page = $this->_helper->getPage($url, $fields, $headers);
                $page = json_decode($page);
                
                foreach ($page->ids as $companyId) {
                    if ( ! $this->companyExists($companyId) ) {
                        $company = $this->getCompanyInfo($companyId);
                        sleep(2);
                    }
                }
                sleep(3);
            }
        }
        
        private function getCompanyInfo($companyId)
        {
            echo "\nGetting company info: $companyId\n";
            
            $url = "https://angel.co/follows/tooltip?type=Startup&id=" . $companyId;
            $fields = array();
            $headers = array('X-Requested-With: XMLHttpRequest');
            
            $htmlPage = $this->_helper->getPage($url, $fields, $headers);
            $companyUrl = $this->getCompanyLink($htmlPage);
            
            $company = new Models\Models_Company();
            
            if ($companyUrl) {
                echo "compannyUrl: " . $companyUrl . "\n";
                $htmlPage = $this->_helper->getPage($companyUrl);
                // $this->_helper->write($companyId .".html", $htmlPage);

                $finder = $this->getElementFinder($htmlPage);
                
                if ($finder) {
                    $name = $this->getName($finder);
                    $location = $this->getLocation($finder);
                    $domain = $this->getDomain($finder);
                    
                    $siteCompanyId = $this->generateSiteCompanyId($companyId);
                    $company->setSiteCompanyId($siteCompanyId);
                    $company->setName($name);
                    $company->setLocation($location);
                    $company->setDomain($domain);
                    $company->save();
                    
                    echo "name: " . $name . "\n";
                    echo "location: " . $location . "\n";
                    echo "domain: " . $domain . "\n";
                }
                else {
                    echo "Fail to get page '$companyUrl'\n";
                }
            }
            return $company;
        }
        
        private function getElementFinder($htmlPage)
        {
            $dom = new \DOMDocument();
            $dom->loadHTML($htmlPage);
            return new \DomXPath($dom);
        }

        private function getName($finder)
        {
            $classname = "name_holder";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'name')]";
            $nodes = $finder->query($query);
            $name = split(" · ", $nodes->item(0)->nodeValue)[0];
            return $name;
        }
        
        private function getLocation($finder)
        {
            $classname = "main standard g-lockup larger";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'tag')]";
            $nodes = $finder->query($query);
            $location = split(" · ", $nodes->item(0)->nodeValue)[0];
            $location = preg_replace( "/\n/", "", $location);
            return $location;
        }
        
        private function getDomain($finder)
        {
            $classname = "links standard";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'company_url')]";
            $nodes = $finder->query($query);
            $domain = $nodes->item(0)->attributes->getNamedItem("href")->nodeValue;
            return $domain;
        }
        
        private function getCompanyLink($htmlPage)
        {
            $dom = new \DOMDocument();
            $dom->loadHTML($htmlPage);

            $finder = new \DomXPath($dom);
            $classname = "startup-link";
            $nodes = $finder->query("//*[contains(@class, '$classname')]");
            
            return $nodes->item(0)->attributes->getNamedItem("href")->nodeValue;
        }
        
        private function generateSiteCompanyId($companyId)
        {
            return $this->_siteName . "_" . $companyId;
        }
        
        private function companyExists($companyId)
        {
            $company = new Models\Models_Company();
            $siteCompanyId = $this->generateSiteCompanyId($companyId);
            return $company->getBySiteCompanyId($siteCompanyId);
        }
    }
