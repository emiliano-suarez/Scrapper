<?php

    namespace Scrapper\Models;

    class Models_Employee {

        private $_companyId;
        private $_name;
        private $_social;

        public function __construct()
        {
        }

        public function setCompanyId($companyId)
        {
            $this->_companyId = $companyId;
        }
        
        public function getCompanyId()
        {
            return $this->_companyId;
        }

        public function setName($name)
        {
            $this->_name = $name;
        }
        
        public function getName()
        {
            return $this->_name;
        }

        public function setSocial($social)
        {
            $this->_social = $social;
        }
        
        public function getSocial()
        {
            return $this->_social;
        }

    }
