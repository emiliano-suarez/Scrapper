<?php

namespace Scrapper\Parser;
use Scrapper\Lib as Lib;
use Scrapper\Models as Models;

class Parser_Gust implements Parser_Interface {

    const MAX_FOUNDERS = 5;
    const MAX_EMPLOYEES = 10;
    
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
        $url = "https://gust.com/search/new?category=everything&partial=results";
        $headers = array('X-Requested-With: XMLHttpRequest');

        $pageNumber = 1;
        $done = false;
    
        do {
            echo "\nPage: " . $pageNumber . "\n";
            if ($pageNumber > 1) {
              $pageUrl = $url.'&page='.$pageNumber;
            } else {
              $pageUrl = $url;
            }

            $page = $this->_helper->getPage($pageUrl, null, $headers);

            if ($page) {
                $page = '<body>'.$page.'</body>';
                $finder = $this->_getElementFinder($page);
                
                $query = '//li[@class="list-group-item clearfix"]';
                $companies = $finder->query($query);
                $counter = 0;
                while ($counter < $companies->length) {
                  $this->_processCompany($finder, $companies->item($counter));
                  $counter++;
                }
                
                $query = '//ul[contains(@class, "pagination")]/li[@class="last")]/a';
                $nodes = $finder->query($query);
                $lastPage = ($nodes->length > 0);
            }
            $hour = date('H');
            if (($hour >= 20) || ($hour <= 7)) {
              //After 20Hs until 7Hs, just go super super slow
              sleep(3600);
            } else {
              sleep(30);
            }

            $pageNumber++;
            if ($lastPage) {
              $done = true;
            }
        }
        while(! $done);
    }

    private function _getElementFinder($htmlPage)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($htmlPage);
        return new \DomXPath($dom);
    }
    
    private function _processCompany($finder, $companyNode)
    {
        $companyLinkQuery = './/div[@class="media-body"]/h3/a';
        $nodes = $finder->query($companyLinkQuery, $companyNode);
        $name = $nodes->item(0)->nodeValue;
        $link = 'https://gust.com/'.$nodes->item(0)->getAttribute('href');
        $companyId = substr($link, strrpos($link, '/'));

        echo "Parsing company ".$name.' ('.$companyId.')'.PHP_EOL;

        $typeQuery = './/div[@class="col-md-2"]/small[contains(@class, "text-muted")]';
        $nodes = $finder->query($typeQuery, $companyNode);
        $type = str_replace(PHP_EOL, '', $nodes->item(0)->nodeValue);

        $locationQuery = './/div[@class="text--secondary"]';
        $nodes = $finder->query($locationQuery, $companyNode);
        $parts = explode(PHP_EOL, $nodes->item(0)->nodeValue);
        $location = $parts[1];
        $industry = $parts[2];
        
        $descriptionQuery = './/small[@role="description"]';
        $nodes = $finder->query($descriptionQuery, $companyNode);
        $description = $nodes->item(0)->nodeValue;
        
        sleep(15);
        $headers = array('X-Requested-With: XMLHttpRequest');
        $page = $this->_helper->getPage($link, null, $headers);
      
        $company = $this->_buildCompanyInfo($page, $companyId, $type, $location, $industry, $description, $name);
    }

    private function _buildCompanyInfo($page, $gustId, $type, $location, $industry, $description, $name) {
      $finder = $this->_getElementFinder($page);
      
      $markets = implode(',', explode(' / ', $industry));
      
      $domainQuery = '//ul[@class="list-group list-group--split-text gust-margin--no-margin--bottom"]/li[@class="list-group-item" and contains(text(), "Website")]/span/a';
      $nodes = $finder->query($domainQuery);
      $domain = $nodes->item(0)->nodeValue;

      if (! $this->_shouldSkip($gustId, $domain)) {
        $siteCompanyId = $this->_generateSiteCompanyId($gustId);
        
        $company = new Models\Models_Company();
        
        $company->setSiteName($this->_siteName);
        $company->setSiteCompanyId($siteCompanyId);
        $company->setName($name);
        $company->setType($type);
        $company->setMarkets($markets);
        $company->setLocation($location);
        $company->setDomain($domain);
        $company->setSocial('');
        $company->setDescription($description);

        $scrapperCompanyId = $company->save();

        $this->_getEmployees($finder, $scrapperCompanyId);
        $this->_getAdvisors($finder, $scrapperCompanyId);

      } else {
        echo '  Skipping company (Already fetched or invalid domain)'.PHP_EOL;
      }
    }
    
    private function _getEmployees($finder, $companyId) {
      echo '  Extracting Employees ...'.PHP_EOL;
    
      $managersQuery = '//div[@class="gust-margin--extra-small--bottom" and @id="management"]//div[@class="panel-body"]/ul/li';
      $managerNodes = $finder->query($managersQuery);

      $counter = 0;
      while ($counter < $managerNodes->length) {
        $node = $managerNodes->item($counter);
        
        $positionQuery = './/div[@class="media media--social-contact-card"]//div[@class="media-heading__role"]';
        $positionNode = $finder->query($positionQuery, $node);
        $position = $positionNode->item(0)->nodeValue;
        if ($this->_shouldFetchEmployeeData($position)) {
          $nameQuery = './/div[@class="media media--social-contact-card"]//div[@class="media-heading__name"]';
          $nameNode = $finder->query($nameQuery, $node);
          $name = str_replace(PHP_EOL, '', $nameNode->item(0)->nodeValue);

          echo '    Getting info for '.$name.PHP_EOL;
          
          $socialQuery = './/div[@class="media media--social-contact-card"]//div[@class="media-heading"]/a';
          $socialLinks = $finder->query($socialQuery, $node);
          $i = 0;
          $socialLinks = array();
          while ($i < $socialLinks->length) {
            $socialLinks[] = $socialLinks->item($i)->getAttribute('href');
            $i++;
          }
          $social = implode(',', $socialLinks);
          $social = str_replace(PHP_EOL, '', $social);
          
          if ($this->_isFounder($position)) {
            $employee = new Models\Models_Founder();
          } else {
            $employee = new Models\Models_Employee();
            $employee->setTitle($position);
          }
          
          $employee->setCompanyId($companyId);
          $nameParts = explode(' ', $name);
          $employee->setFirstName($nameParts[0]);
          $employee->setLastName($nameParts[count($nameParts) - 1]);
          $employee->setSocial($social);
          
          $employee->save();
        }
        
        $counter++;
      }
    }

    private function _getAdvisors($finder, $companyId) {
      echo '  Extracting Advisors ...'.PHP_EOL;
    
      $advisorsQuery = '//div[@class="gust-margin--extra-small--bottom" and @id="advisors"]//div[@class="panel-body"]/ul/li//div[@class="media media--contact-card"]';
      $advisorNodes = $finder->query($advisorsQuery);
      
      $title = 'Advisor';
      $counter = 0;
      while ($counter < $advisorNodes->length) {
        $node = $advisorNodes->item($counter);

        $nameQuery = './/div[@class="media-heading__name"]';
        $nameNode = $finder->query($nameQuery, $node);
        $name = str_replace(PHP_EOL, '', $nameNode->item(0)->nodeValue);
         
        echo '    Getting info for '.$name.PHP_EOL;
         
        $employee = new Models\Models_Employee();
        $employee->setCompanyId($companyId);
        $employee->setTitle($title);
        $nameParts = explode(' ', $name);
        $employee->setFirstName($nameParts[0]);
        $employee->setLastName($nameParts[count($nameParts) - 1]);
        $employee->setSocial('');
        
        $employee->save();
        
        $counter++;
      }
      
    }
    
    private function _generateSiteCompanyId($companyId)
    {
        return $this->_siteName . "_" . $companyId;
    }

    private function _isFounder($title) {
        $keywords = array(
            "co-founder",
            "cofounder",
            "founder",
        );

        foreach ($keywords as $keyword) {
            if (strpos(strtolower($title), $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function _shouldFetchEmployeeData($description)
    {
        $keywords = array(
            "co-founder",
            "cofounder",
            "founder",
            "marketing",
            "PR",
            "communications",
            "public relations",
            "ceo",
            "coo",
            "director",
            "president",
        );

        foreach ($keywords as $keyword) {
            if (strpos(strtolower($description), $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function _shouldSkip($companyId, $domain)
    {
        $forbiddenDomains = array('google.com', 'twitter.com', 'facebook.com', 'linkedin.com', '');
        if (in_array($domain, $forbiddenDomains)) {
          return true;
        }
        $company = new Models\Models_Company();
        $siteCompanyId = $this->_generateSiteCompanyId($companyId);
        $companyData = $company->getBySiteCompanyId($siteCompanyId);
        if (! $companyData) {
          return $companyData;
        } else {
          $companyData = $company->getByDomain($domain);
          return $companyData;
        }
    }
}
