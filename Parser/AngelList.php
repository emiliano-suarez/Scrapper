<?php

    namespace Scrapper\Parser;
    use Scrapper\Lib as Lib;
    use Scrapper\Models as Models;

    class Parser_AngelList implements Parser_Interface {

        const MAX_FOUNDERS = 3;
        const MAX_EMPLOYEES = 2;
        
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

                    $fields = "filter_data[stage][]=Seed&filter_data[stage][]=Series+A&filter_data[stage][]=Series+B&filter_data[stage][]=Series+C";
                    $fields .= "&filter_data[company_types][]=" . $type;
                    $fields .= "&sort=signal&page=" . $pageNumber;

                    $this->_companyType = "";

                    if ($type) {
                        $this->_companyType = $type;
                    }

                    $page = $this->_helper->getPage($url, $fields, $headers);

                    if ($page) {
                        $page = json_decode($page);

                        foreach ($page->ids as $companyId) {
                            if ( ! $this->companyExists($companyId) ) {
                                $company = $this->getCompanyInfo($companyId);
                                sleep(2);
                            }
                            $companyCounter++;
                        }
                    }

                    sleep(2);

                    $pageNumber++;
                }
                while($companyCounter < $page->total);
            }
        }

        private function getCompanyInfo($companyId)
        {
            $companyUrl = $this->getCompanyLink($companyId);

            if ($companyUrl) {
                echo "compannyUrl: " . $companyUrl . "\n";
                $htmlPage = $this->_helper->getPage($companyUrl);

                if ($htmlPage) {
                    $company = new Models\Models_Company();

                    $finder = $this->getElementFinder($htmlPage);

                    if ($finder) {
                        $name = $this->getName($finder);
                        $markets = $this->getMarkets($finder);
                        $location = $this->getLocation($finder);
                        $domain = $this->getDomain($finder);
                        $social = $this->getSocial($finder);
                        $description = $this->getDescription($finder);
                        $siteCompanyId = $this->generateSiteCompanyId($companyId);

                        echo "Company: " . $name . "\n";
                        
                        $company->setSiteName($this->_siteName);
                        $company->setSiteCompanyId($siteCompanyId);
                        $company->setName($name);
                        $company->setType($this->_companyType);
                        $company->setMarkets($markets);
                        $company->setLocation($location);
                        $company->setDomain($domain);
                        $company->setSocial($social);
                        $company->setDescription($description);
                        
                        $scrapperCompanyId = $company->save();

                        $this->getFounders($scrapperCompanyId, $finder);
                        
                        if ($this->shouldFetchEmployeesData($description)) {
                            $this->getEmployees($scrapperCompanyId, $finder);
                        }

                        return $company;
                    }
                    else {
                        echo "Fail to get page '$companyUrl'\n";
                    }
                }
            }
            return false;
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

        private function getCompanyLink($companyId)
        {
            echo "\nGetting company info: $companyId\n";

            $url = "https://angel.co/follows/tooltip?type=Startup&id=" . $companyId;
            $fields = array();
            $headers = array('X-Requested-With: XMLHttpRequest');

            $htmlPage = $this->_helper->getPage($url, $fields, $headers);

            if ($htmlPage) {
                $dom = new \DOMDocument();
                $dom->loadHTML($htmlPage);

                $finder = new \DomXPath($dom);
                $classname = "startup-link";
                $nodes = $finder->query("//*[contains(@class, '$classname')]");

                return $nodes->item(0)->attributes->getNamedItem("href")->nodeValue;
            }
            else {
                return "";
            }
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

        private function shouldFetchEmployeesData($description)
        {
        return true;
            $keywords = array(
                "marketing",
                "PR",
                "communications",
                "public relations",
            );

            foreach ($keywords as $keyword) {
                if (strpos(strtolower($description), $keyword) !== false) {
                    return true;
                }
            }
            return false;
        }

        private function getFounders($scrapperCompanyId, $finder)
        {
            $classname = "founders section";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'larger roles')]";

            $nodeElements = $finder->query($query);
            
            if ($nodeElements->length > 0) {
              $nodeElement = $nodeElements->item(0)->getElementsByTagName("a");

              $i = 0;
              $countFounders = 0;
              
              while ( ($i < $nodeElement->length)
                      && ($countFounders <= $this::MAX_FOUNDERS) ) {
                  $nodeValue = $nodeElement->item($i)->nodeValue;
                  if ($nodeValue && (strpos($nodeValue, "@") === false) ) {
                      $profileName = $nodeElement->item($i)->nodeValue;
                      $profileLink = $nodeElement->item($i)->attributes->getNamedItem("href")->nodeValue;
                      $this->getFounderProfile($profileLink,
                                              $profileName,
                                              $scrapperCompanyId);

                      $countFounders++;
                  }
                  $i++;
              }
            }
        }
        
        private function getEmployees($scrapperCompanyId, $finder)
        {
            $query = "//*[contains(@class, 'section team')]";
            $query .= "//*[(contains(@class, 'group')) and not (contains(@class, 'view_all'))]";
            
            $nodeElements = $finder->query($query);
            
            if ($nodeElements->length > 0) {
              $nodeElement = $nodeElements->item(0)->getElementsByTagName("a");
            
              $i = 0;
              $countEmployees = 0;
              
              while ( ($i < $nodeElement->length)
                      && ($countEmployees <= $this::MAX_EMPLOYEES) ) {
                  $nodeValue = $nodeElement->item($i)->nodeValue;
                  if ($nodeValue && (strpos($nodeValue, "@") === false) ) {
                      $profileName = $nodeElement->item($i)->nodeValue;
                      $profileLink = $nodeElement->item($i)->attributes->getNamedItem("href")->nodeValue;
                      $this->getEmployeeProfile($profileLink,
                                              $profileName,
                                              $scrapperCompanyId);

                      $countEmployees++;
                  }
                  $i++;
              }
            }
        }

        private function getFounderProfile($profileLink,
                                            $profileName,
                                            $companyId)
        {
            echo "\nFounder Name: " . $profileName . "\n";
            echo "Founder Link: " . $profileLink . "\n\n";
            $htmlPage = $this->_helper->getPage($profileLink);
            
            if ($htmlPage) {
                $founder = new Models\Models_Founder();
                
                $finder = $this->getElementFinder($htmlPage);

                if ($finder) {
                    $social = $this->getProfileSocialInfo($finder);
                    $nameArray = $this->splitProfileName($profileName);
                    
                    $founder->setCompanyId($companyId);
                    $founder->setFirstName($nameArray["first_name"]);
                    $founder->setLastName($nameArray["last_name"]);
                    $founder->setSocial($social);

                    $founder->save();
                }
            }
        }
        
        private function getEmployeeProfile($profileLink,
                                            $profileName,
                                            $companyId)
        {
            echo "\nEmployee Name: " . $profileName . "\n";
            echo "Employee Link: " . $profileLink . "\n\n";
            $htmlPage = $this->_helper->getPage($profileLink);
            
            if ($htmlPage) {
                $employee = new Models\Models_Employee();
                
                $finder = $this->getElementFinder($htmlPage);

                if ($finder) {
                    $social = $this->getProfileSocialInfo($finder);
                    $nameArray = $this->splitProfileName($profileName);
                    
                    $employee->setCompanyId($companyId);
                    $employee->setFirstName($nameArray["first_name"]);
                    $employee->setLastName($nameArray["last_name"]);
                    $employee->setSocial($social);

                    $employee->save();
                }
            }
        }
        
        private function getProfileSocialInfo($finder)
        {
            $classname = "darkest";
            $query = "//*[contains(@class, '$classname')]//*[(contains(@class, 'link'))]";
            $nodes = $finder->query($query);

            $socialArray = array();
            for ($i = 0; $i < $nodes->length; $i++) {
            
                if (isset($nodes->item($i)->attributes->getNamedItem("href")->nodeValue)) {
                    $socialLink = $nodes->item($i)->attributes->getNamedItem("href")->nodeValue;
                    $socialArray[] = $socialLink;
                }
            }

            $social = implode(",", $socialArray);
            $social = preg_replace( "/\n/", "", $social);
            
            return $social;
        }
        
        private function splitProfileName($profileName)
        {
            $firstName = "";
            $lastName = "";
            
            if ($profileName) {
                $nameArray = explode(" ", $profileName);
                $firstName = array_shift($nameArray);
                if (count($nameArray)) {
                    $lastName = implode(" ", $nameArray);
                }
            }
            
            $result = array(
                        "first_name" => $firstName,
                        "last_name" => $lastName,
                      );

            return $result;
        }
    }
