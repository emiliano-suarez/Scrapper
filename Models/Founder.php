<?php

    namespace Scrapper\Models;
    use Scrapper\Da as Da;

    class Models_Founder {

        private $_companyId;
        private $_firstName;
        private $_lastName;
        private $_social;

        public function setCompanyId($companyId)
        {
            $this->_companyId = $companyId;
        }
        
        public function getCompanyId()
        {
            return $this->_companyId;
        }

        public function setFirstName($firstName)
        {
            $this->_firstName = $firstName;
        }
        
        public function getFirstName()
        {
            return $this->_firstName;
        }

        public function setLastName($lastName)
        {
            $this->_lastName = $lastName;
        }
        
        public function getLastName()
        {
            return $this->_lastName;
        }

        public function setSocial($social)
        {
            $this->_social = $social;
        }
        
        public function getSocial()
        {
            return $this->_social;
        }

        public function save()
        {
            $companyId = $this->_companyId;
            $firstName = $this->_firstName ? $this->_firstName : "";
            $lastName = $this->_lastName ? $this->_lastName : "";
            $social = $this->_social ? $this->_social : "";

            return Da\Da_Founder::save($companyId, $firstName,
                                       $lastName, $social);
        }
    }
