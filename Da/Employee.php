<?php

    namespace Scrapper\Da;
    use Scrapper\Da as Da;

    class Da_Employee {

        private static $connections = array();

        public static function save($companyId,
                                    $firstName,
                                    $lastName,
                                    $social)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_WRITE");

            $sql = "INSERT INTO scrapper_employee
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
    }
