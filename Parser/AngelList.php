<?php

    namespace Scrapper\Parser;
    use Scrapper\Lib as Lib;
    use Scrapper\Models as Models;

    class Parser_AngelList implements Parser_Interface {

        private $_siteName;
        private $_config = null;
        private $_helper;
        private $_companyList = array();
        private $_types = array(
                              "Startup",
                              "VC Firm",
                              "Incubator",
                              "Early Stage",
                              "Mobile App",
                              "Internet",
                              "", // Needed to do not filter by this field
                          );
        private $_companyType = null;

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
            
            foreach ($this->_types as $type) {
                $companyCounter = 0;
                $pageNumber = 1;
                
                echo "Getting type: " . $type . "\n";
                
                do {
                    echo "\nPage: " . $pageNumber . "\n";
                    
                    $fields = array(
                            'filter_data[stage][]' => 'Seed',
                            'filter_data[stage][]' => 'Serie+A',
                            'filter_data[stage][]' => 'Serie+B',
                            'filter_data[stage][]' => 'Serie+C',
                            'sort' => 'signal',
                            'page' => $pageNumber,
                    );
                    
                    $this->_companyType = "";
                    
                    if ($type) {
                        $fields['filter_data[company_types][]'] = $type;
                        $this->_companyType = $type;
                    }
                    
                    $page = $this->_helper->getPage($url, $fields, $headers);
                    $page = json_decode($page);

                    foreach ($page->ids as $companyId) {
                        if ( ! $this->companyExists($companyId) ) {
                            $company = $this->getCompanyInfo($companyId);
                            sleep(2);
                        }
                        $companyCounter++;
                    }
                    sleep(2);
                    
                    $pageNumber++;
                }
                while($companyCounter < $page->total);
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
                    $markets = $this->getMarkets($finder);
                    $location = $this->getLocation($finder);
                    $domain = $this->getDomain($finder);
                    $social = $this->getSocial($finder);
                    $description = $this->getDescription($finder);
                    $siteCompanyId = $this->generateSiteCompanyId($companyId);
                    
                    $company->setSiteCompanyId($siteCompanyId);
                    $company->setName($name);
                    $company->setType($this->_companyType);
                    $company->setMarkets($markets);
                    $company->setLocation($location);
                    $company->setDomain($domain);
                    $company->setSocial($social);
                    $company->setDescription($description);
                    
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
            $name = explode(" · ", $nodes->item(0)->nodeValue)[0];
            return $name;
        }
        
        private function getMarkets($finder)
        {
            $classname = "main standard g-lockup larger";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'tag')]";
            $nodes = $finder->query($query);
            $marketsArray = explode(" · ", $nodes->item(0)->nodeValue);
            
            // Remove the first element because it is the location
            array_shift($marketsArray);
            
            $markets = implode(",", $marketsArray);
            $markets = preg_replace( "/\n/", "", $markets);
            return $markets;
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
        
        private function getSocial($finder)
        {
            $classname = "links standard";
            $query = "//*[contains(@class, '$classname')]//*[(contains(@class, 'link')) and not (contains(@class, 'blank'))]//*[contains(@class, '_url')]";
            $nodes = $finder->query($query);

            $socialArray = array();
            for ($i = 0; $i < $nodes->length; $i++) {
                // Don't concatenate 'company_url'
                if ("company_url" != $nodes->item($i)->attributes->getNamedItem("class")->nodeValue) {
                    $socialArray[] = $nodes->item($i)->attributes->getNamedItem("href")->nodeValue;
                }
            }

            $social = implode(",", $socialArray);
            $social = preg_replace( "/\n/", "", $social);

            return $social;
        }
        
        private function getDescription($finder)
        {
            $classname = "product_desc editable_region";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'content')]";
            $nodes = $finder->query($query);
            $description = $nodes->item(0)->nodeValue;
            return $description;
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
