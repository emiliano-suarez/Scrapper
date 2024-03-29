<?php

    namespace Scrapper\Da;
    use Scrapper\Da as Da;

    class Da_Employee {

        private static $connections = array();

        public static function save($companyId,
                                    $firstName,
                                    $lastName,
                                    $title,
                                    $social)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_WRITE");

            $sql = "INSERT INTO scrapper_employee
                        (
                            company_id,
                            first_name,
                            last_name,
                            title,
                            social
                        )
                    VALUES
                        (?, ?, ?, ?, ?);";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $companyId);
            $parameters->addParameter("STRING", $firstName);
            $parameters->addParameter("STRING", $lastName);
            $parameters->addParameter("STRING", $title);
            $parameters->addParameter("STRING", $social);

            $value = $dbConnection->execute($sql, $parameters);
            return $value;
        }

        public static function getEmployeesByCompanyId($companyId)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_READ");

            $sql = "SELECT
                        id, company_id, first_name, last_name, title, social
                    FROM
                        scrapper_employee
                    WHERE
                        company_id = ?";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $companyId);

            $value = $dbConnection->executeQuery($sql, $parameters);

            return $value;
        }
    }
