<?php

    namespace Scrapper\Da;
    use Scrapper\Da as Da;

    class Da_Founder {

        private static $connections = array();

        public static function save($companyId,
                                    $name,
                                    $social)
        {
            $dbConnection = Da\Da_DbConnectionProvider::getConnection("SITE_WRITE");

            $sql = "INSERT INTO scrapper_employee
                        (
                            company_id,
                            name,
                            social
                        )
                    VALUES
                        (?, ?, ?);";

            $parameters = new Da\dbParameters();
            $parameters->addParameter("INT", $companyIdd);
            $parameters->addParameter("STRING", $name);
            $parameters->addParameter("STRING", $social);

            $value = $dbConnection->execute($sql, $parameters);
            return $value;
        }
    }
