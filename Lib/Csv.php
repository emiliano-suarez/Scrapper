<?php

    namespace Scrapper\Lib;
    use Scrapper\Lib as Lib;
    use Scrapper\Models as Models;
    
    class Lib_Csv {
    
        private $_siteName;
        private $helper;
        
        public function __construct($siteName = '')
        {
            $this->_siteName = $siteName;
            $this->_helper = new Lib\Lib_Helper();
        }

        public function generate()
        {
            $companyModel = new Models\Models_Company();
            $companies = $companyModel->getBySiteName($this->_siteName);

            $filename = $this->_siteName . ".csv";
            
            $header = $this->getCsvHeader();
            $this->_helper->write($filename, $header);
                
            foreach ($companies as $company) {
                $founders = $this->getFounders($company["ID"]);
                $employees = $this->getEmployees($company["ID"]);
                
                $companyData = $this->mergeCompanyData($company,
                                                       $founders,
                                                       $employees);
                $this->_helper->write($filename, $companyData);
            }
        }

        private function getCsvHeader()
        {
            $header  = "COMPANY NAME,";
            $header .= "TYPE,";
            $header .= "MARKETS,";
            $header .= "LOCATION,";
            $header .= "DOMAIN,";
            $header .= "SOCIAL,";
            $header .= "DESCRIPTION,";
            $header .= "MORE INFO,";
            $header .= "FIRST NAME,";
            $header .= "LAST NAME,";
            $header .= "SOCIAL,";
            $header .= "\n";
            
            return $header;
        }
        
        private function getFounders($companyId)
        {
            $founderModel = new Models\Models_Founder();
            return $founderModel->getFoundersByCompanyId($companyId);
        }
        
        private function getEmployees($companyId)
        {
            $employeeModel = new Models\Models_Employee();
            return $employeeModel->getEmployeesByCompanyId($companyId);
        }
        
        private function mergeCompanyData($company, $founders = array(),
                                          $employees = array())
        {
            $companyPlainInfo  = "\"" . $company["NAME"] . "\",";
            $companyPlainInfo .= "\"" . $company["TYPE"] . "\",";
            $companyPlainInfo .= "\"" . $company["MARKETS"] . "\",";
            $companyPlainInfo .= "\"" . $company["LOCATION"] . "\",";
            $companyPlainInfo .= "\"" . $company["DOMAIN"] . "\",";
            $companyPlainInfo .= "\"" . $company["SOCIAL"] . "\",";
            $companyPlainInfo .= "\"" . $company["DESCRIPTION"] . "\",";
            
            $companyPlainInfo .= "FOUNDERS:,";
            foreach($founders as $founder) {
                $companyPlainInfo .= "\"" . $founder["FIRST_NAME"] . "\",";
                $companyPlainInfo .= "\"" . $founder["LAST_NAME"] . "\",";
                $companyPlainInfo .= "\"" . $founder["SOCIAL"] . "\",";
            }

            $companyPlainInfo .= "EMPLOYEES:,";
            foreach($employees as $employee) {
                $companyPlainInfo .= "\"" . $employee["FIRST_NAME"] . "\",";
                $companyPlainInfo .= "\"" . $employee["LAST_NAME"] . "\",";
                $companyPlainInfo .= "\"" . $employee["SOCIAL"] . "\",";
            }
            
            $companyPlainInfo .= "\n";
            
            return $companyPlainInfo;
        }
    }
