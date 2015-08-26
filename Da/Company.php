<?php

    namespace Scrapper\Da;
    use Scrapper\Da as Da;

    class Da_Company {

        private static $connections = array();

        public static function save($siteCompanyId,
                                    $name,
                                    $type,
                                    $markets,
                                    $location,
                                    $domain,
                                    $social,
                                    $description)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_WRITE");

            $sql = "INSERT INTO scrapper_company
                        (
                            site_company_id,
                            name,
                            type,
                            markets,
                            location,
                            domain,
                            social,
                            description
                        )
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?);";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("STRING", $siteCompanyId);
            $parameters->addParameter("STRING", $name);
            $parameters->addParameter("STRING", $type);
            $parameters->addParameter("STRING", $markets);
            $parameters->addParameter("STRING", $location);
            $parameters->addParameter("STRING", $domain);
            $parameters->addParameter("STRING", $social);
            $parameters->addParameter("STRING", $description);

            $value = $dbConnection->execute($sql, $parameters);

            if ($value) {
                return $dbConnection->getLastId();
            }
            else {
                return false;
            }
        }

        public static function getBySiteCompanyId($siteCompanyId)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_READ");

            $sql = "SELECT
                        id, site_company_id, name, type, markets,
                        location, domain, social, description
                    FROM
                        scrapper_company
                    WHERE
                        site_company_id = ? ";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("STRING", $siteCompanyId);

            $value = $dbConnection->executeQuery($sql, $parameters);

            return $value;
        }

        public static function getById($id)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_READ");

            $sql = "SELECT
                        id, site_company_id, name, type, markets,
                        location, domain, social, description
                    FROM
                        scrapper_company
                    WHERE
                        id = ? ";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $id);

            $value = $dbConnection->executeQuery($sql, $parameters);

            return $value;
        }
    }
