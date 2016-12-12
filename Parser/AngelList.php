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
        private $_stages = array(
                               "Seed",
                               "Series+A",
                               "Series+B",
                               "Series+C",
                           );
        private $_types = array(
                              "Startup",
                              "VC Firm",
                              "Incubator",
                              "Early Stage",
                              "Mobile App",
                              "Internet",
                              "", // Needed to do not filter by this field
                          );
        private $locations = null;
        private $_companyType = null;

        public function __construct($siteName = "")
        {
            $this->_siteName = $siteName;
            $this->_helper = new Lib\Lib_Helper();
            require __DIR__."/../Config/AngelList.php";
            $this->locations = $locations;
        }

        public function run()
        {
            $this->getCompanies();
        }

        private function getCompanies()
        {
            $url = "https://angel.co/company_filters/search_data";
            $headers = array('X-Requested-With: XMLHttpRequest');

            foreach ($this->locations as $location) {
                echo "Getting location: " . $location . "\n";
                foreach ($this->_types as $type) {
                    echo "Getting type: " . $type . "\n";
                    
                    foreach ($this->_stages as $stage) {
                        echo "Getting stage: " . $stage . "\n";

                        $companyCounter = 0;
                        $pageNumber = 1;
                    
                        do {
                            echo "\nPage: " . $pageNumber . "\n";

                            $fields = "filter_data[stage]=" . $stage;
                            $fields .= "&filter_data[company_types][]=" . $type;
                            if ($location != '') {
                              $fields .= "&locations[]=" . $location;
                            }
                            $fields .= "&sort=joined&page=" . $pageNumber;

                            $this->_companyType = "";

                            if ($type) {
                                $this->_companyType = $type;
                            }

                            $page = $this->_helper->getPage($url, $fields, $headers);

                            if ($page) {
                                $page = json_decode($page);
                                
                                if (isset($page->ids)) {
                                    foreach ($page->ids as $companyId) {
                                        $company = $this->getCompanyInfo($companyId);
                                        sleep(2);
                                        $companyCounter++;
                                    }
                                }
                                else {
                                    echo "Warning: empty ids !!!\n";
                                }
                            }
                            sleep(2);

                            $pageNumber++;
                        }
                        while(isset($page->total) && $companyCounter < $page->total);
                    }
                }
            }
        }

        private function getCompanyInfo($companyId)
        {
            $companyUrl = $this->getCompanyLink($companyId);

            if ($companyUrl) {
                echo "compannyUrl: " . $companyUrl . "\n";
                $htmlPage = $this->_helper->getPage($companyUrl);

                if ($htmlPage) {
                    $finder = $this->getElementFinder($htmlPage);
                    $domain = $this->getDomain($finder);
                    
                    if ( ! $this->companyExists($companyId, $domain) ) {
                
                      $company = new Models\Models_Company();

                      if ($finder) {
                          $name = $this->getName($finder);
                          $markets = $this->getMarkets($finder);
                          $location = $this->getLocation($finder);
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
                          
                          $this->getEmployees($scrapperCompanyId, 'employees_section', $finder);
                          $this->getEmployees($scrapperCompanyId, 'advisors_section', $finder);

                          return $company;
                      }
                      else {
                          echo "Fail to get page '$companyUrl'\n";
                      }
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
            $classname = "js-location_tag_holder";
            $query = "//*[contains(@class, '$classname')]//*[contains(@class, 'js-location_tags')]/a";
            $nodes = $finder->query($query);
            $location = $nodes->item(0)->nodeValue;
            return $location;
        }

        private function getDomain($finder)
        {
            $classname = "tags_and_links _a";
            $query = "//*[contains(@class, '$classname')]//span[contains(@class, 'links')]//*[contains(@class, 'company_url')]";
            $nodes = $finder->query($query);
            $domain = $nodes->item(0)->attributes->getNamedItem("href")->nodeValue;
            return $domain;
        }

        private function getSocial($finder)
        {
            $classname = "tags_and_links _a";
            $query = "//*[contains(@class, '$classname')]//span[contains(@class, 'links')]//*[(contains(@class, 'link')) and not (contains(@class, 'blank'))]//*[contains(@class, '_url')]";
            $nodes = $finder->query($query);

            $socialArray = array();
            for ($i = 0; $i < $nodes->length; $i++) {
                // Don't concatenate 'company_url'
                if (strpos($nodes->item($i)->attributes->getNamedItem("class")->nodeValue, "company_url") === false ) {
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

        private function companyExists($companyId, $domain)
        {
            $company = new Models\Models_Company();
            $siteCompanyId = $this->generateSiteCompanyId($companyId);
            $companyData = $company->getBySiteCompanyId($siteCompanyId);
            if (! $companyData) {
              return $companyData;
            } else {
              $companyData = $company->getByDomain($domain);
              return $companyData;
            }
        }

        private function shouldFetchEmployeeData($description)
        {
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
        
        private function getEmployees($scrapperCompanyId, $employeeType, $finder)
        {
            $query = "//*[contains(@class, 'section team')]";
            $query .= "//*[(contains(@class, 'group')) and preceding-sibling::h4[1][@data-tips_selector='".$employeeType."']]";
            
            $nodeElements = $finder->query($query);
            
            if ($nodeElements->length > 0) {
              $nodeElement = $nodeElements->item(0)->getElementsByTagName("a");
              $i = 0;
              $countEmployees = 0;
              
              while ( ($i < $nodeElement->length)
                      && ($countEmployees <= $this::MAX_EMPLOYEES) ) {
                  $nodeValue = $nodeElement->item($i)->nodeValue;
                  if ($nodeValue && (strpos($nodeValue, "@") === false) && (strpos($nodeElement->item($i)->attributes->getNamedItem("href")->nodeValue, "https://angel.co/") === 0)) {                  
                      $sibling = $nodeElement->item($i)->parentNode->nextSibling;
                      $titleFound = false;
                      $discard = true;

                      while ((! $titleFound) && ($sibling !== null)) {
                        if ($sibling->nodeType == 1) {
                          $elementClass = $sibling->getAttribute('class');
                          if ($elementClass == 'role_title') {
                            $role = $sibling->nodeValue;
                            $titleFound = true;
                            if ($employeeType == 'advisors_section' || $this->shouldFetchEmployeeData($role)) {
                              $profileName = $nodeElement->item($i)->nodeValue;
                              $profileLink = $nodeElement->item($i)->attributes->getNamedItem("href")->nodeValue;
                              if ($employeeType == 'advisors_section') {
                                $title = 'Advisor';
                              } else {
                                $title = $role;
                              }
                              $discard = false;
                            }
                          }
                        }
                        $sibling = $sibling->nextSibling;
                      }
                      
                      if (! $discard) {
                          $this->getEmployeeProfile($profileLink,
                                                  $profileName,
                                                  $title,
                                                  $scrapperCompanyId);
                          $countEmployees++;
                      }
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
                                            $title,
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
                    $employee->setTitle($title);
                    $employee->setSocial($social);

                    $employee->save();
                }
            }
        }
        
        private function getProfileSocialInfo($finder)
        {
            $classname = "darkest";
            $query = "//*[contains(@class, '$classname')]//*[(contains(@class, 'link'))]/a";
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
