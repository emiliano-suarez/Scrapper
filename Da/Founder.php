<?php

    namespace Scrapper\Da;
    use Scrapper\Da as Da;

    class Da_Founder {

        private static $connections = array();

        public static function save($companyId,
                                    $firstName,
                                    $lastName,
                                    $social)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_WRITE");

            $sql = "INSERT INTO scrapper_founder
                        (
                            company_id,
                            first_name,
                            last_name,
                            social
                        )
                    VALUES
                        (?, ?, ?, ?);";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $companyId);
            $parameters->addParameter("STRING", $firstName);
            $parameters->addParameter("STRING", $lastName);
            $parameters->addParameter("STRING", $social);

            $value = $dbConnection->execute($sql, $parameters);
            return $value;
        }

        public static function getFoundersByCompanyId($companyId)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_READ");

            $sql = "SELECT
                        id, company_id, first_name, last_name, social
                    FROM
                        scrapper_founder
                    WHERE
                        company_id = ?";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $companyId);

            $value = $dbConnection->executeQuery($sql, $parameters);

            return $value;
        }
    }
