<?php

    namespace Scrapper\Models;
    use Scrapper\Da as Da;

    class Models_Company {
    
        private $_siteCompanyId;
        private $_name;
        private $_type;
        private $_markets;
        private $_location;
        private $_domain;
        private $_social;
        private $_description;

        public function setSiteCompanyId($siteCompanyId)
        {
            $this->_siteCompanyId = $siteCompanyId;
        }
        
        public function getSiteCompanyId()
        {
            return $this->_siteCompanyId;
        }
        
        public function setName($name)
        {
            $this->_name = $name;
        }
        
        public function getName()
        {
            return $this->_name;
        }
        
        public function setType($type)
        {
            $this->_type = $type;
        }
        
        public function getType()
        {
            return $this->_type;
        }
        
        public function setMarkets($markets)
        {
            $this->_markets = $markets;
        }
        
        public function getMarkets()
        {
            return $this->_markets;
        }
        
        public function setLocation($location)
        {
            $this->_location = $location;
        }
        
        public function getLocation()
        {
            return $this->_location;
        }
        
        public function setDomain($domain)
        {
            $this->_domain = $domain;
        }
        
        public function getDomain()
        {
            return $this->_domain;
        }
        
        public function setSocial($social)
        {
            $this->_social = $social;
        }
        
        public function getSocial()
        {
            return $this->_social;
        }
        
        public function setDescription($description)
        {
            $this->_description = $description;
        }
        
        public function getDescription()
        {
            return $this->_description;
        }
        
        public function save()
        {
            $site_company_id = $this->_siteCompanyId ? $this->_siteCompanyId : "";
            $name = $this->_name ? $this->_name : "";
            $type = $this->_type ? $this->_type : "";
            $markets = $this->_markets ? $this->_markets : "";
            $location = $this->_location ? $this->_location : "";
            $domain = $this->_domain ? $this->_domain : "";
            $social = $this->_social ? $this->_social : "";
            $description = $this->_description ? $this->_description : "";

            $result = Da\Da_Company::save($site_company_id, $name, $type, $markets,
                                          $location, $domain, $social, $description);
        }
    }
