<?php

    namespace Scrapper\Da;

    include_once(BASE_DIR . "/Config/DbConf.php");

    class Da_DbConnectionProvider {
        private static $connections = array();

        public static function getConnection($connType)
        {
            if ( ! isset(self::$connections[$connType])) {
                eval ("\$host   = ".$connType."_HOST;");
                eval ("\$user   = ".$connType."_USER;");
                eval ("\$pass   = ".$connType."_PASS;");
                eval ("\$dbName = ".$connType."_DB;");
                $mysqlConn = new \mysqli($host, $user, $pass, $dbName);

                if ($mysqlConn->connect_error) {
                    error_log('Connect Error ('
                                . $mysqlConn->connect_errno
                                . ') '
                                . $mysqlConn->connect_error);
                    return false;
                }

                $dbConnection = new dbConn($mysqlConn);
                self::$connections[$connType] = $dbConnection;
            }
            return self::$connections[$connType];
        }
    }

    class dbConn {
        private $dbConnection;

        public function __construct($mysqlConnection)
        {
            $this->dbConnection = $mysqlConnection;
            $this->dbConnection->query("SET NAMES 'utf8'");
            $this->dbConnection->query("SET CHARSET 'utf8'");
        }

        private function prepare($sql)
        {
            $preparedStatement = $this->dbConnection->prepare($sql);
            if ( ! $preparedStatement) {
                error_log('Connect Error ('
                            . $this->dbConnection->errno
                            . ') '
                            . $this->dbConnection->error);
            }

            return $preparedStatement;
        }

        private function getStatement($sqlStatement, dbParameters $parameters)
        {
            $preparedStatement = $this->prepare($sqlStatement);
            $types = "";
            $values = array();

            if ($preparedStatement) {
                $parameters->resetPointer();
                $parametersCount = $parameters->getParametersCount();
                
                if ($parametersCount > 0) {
                    $iCounter = 0;
                    
                    while ($iCounter < $parametersCount) {
                        $param = $parameters->getParameter();
                        $types .= $param[0];
                        array_push($values, $param[1]);
                        $iCounter++;
                    }

                    $bind = call_user_func_array(array(&$preparedStatement, "bind_param"),
                                                 array_merge(array($types), $this->refValues($values))
                                                );

                    if ( ! $bind) {
                        $preparedStatement->close();
                        error_log('DB Bind Param Error (' . $preparedStatement->errno . ') '. $preparedStatement->error);
                        return false;
                    }
                }
                return $preparedStatement;
            } else {
                return false;
            }
        }

        private function refValues($values) {
            $refs = array();
            foreach ($values as $key => $value) {
                $refs[$key] = &$values[$key]; 
            }
            return $refs; 
        }

        private function buildResult($preparedStatement) {
            $metaData = $preparedStatement->result_metadata();
            $fields = array();
            $fieldsRef = array();
            $result = array();

            while ($field = $metaData->fetch_field()) {
                $fields[$field->name] = 1;
                $fieldsRef[] = &$fields[$field->name];
            }
            $bind = call_user_func_array(array(&$preparedStatement, "bind_result"), $fieldsRef);

            while ($preparedStatement->fetch()) {
                foreach($fields as $key => $value) {
                    $row[strtoupper($key)] = $value;
                }
                array_push($result, $row);
            }
            return $result;
        }

        public function executeQuery($sqlStatement, dbParameters $parameters)
        {
            $preparedStatement = $this->getStatement($sqlStatement, $parameters);

            if ( ! $preparedStatement) {
                return false;
            }

            if ($preparedStatement->execute()) {
                $preparedStatement->store_result();
                $result = $this->buildResult($preparedStatement);
                $preparedStatement->close();
                return $result;
            } else {
                error_log('DB Execute prepared Error (' . $preparedStatement->errno . ') '. $preparedStatement->error);
                return false;
            }
        }

        public function execute($sqlStatement, dbParameters $parameters)
        {
            $preparedStatement = $this->getStatement($sqlStatement, $parameters);

            if (! $preparedStatement)
            return false;

            $result = $preparedStatement->execute();

            if (! $result)
            error_log('DB Execute prepared Error (' . $preparedStatement->errno . ') '. $preparedStatement->error);

            $preparedStatement->close();
            return $result;
        }

        public function getLastId()
        {
            return $this->dbConnection->insert_id;
        }

    }

    class dbParameters
    {
        private static $validTypes = array("INT", "STRING", "TEXT", "DOUBLE");
        private static $typesTranslations = array("INT" => "i", "STRING" => "s", "DOUBLE" => "d", "TEXT" => "b");
        private $paramPointer;
        private $parameters;

        public function __construct()
        {
            $this->paramPointer = 0;
            $this->parameters = array();
        }

        private function _get($propName)
        {
            $value = null;
            if (isset($this->parameters[$propName])) {
                $value = $this->parameters[$propName];
            }
            return $value;
        }

        private function _set($propName, $propValue)
        {
            $this->parameters[$propName] = $propValue;
        }

        public function addParameter($type, $value)
        {
            if (in_array($type, self::$validTypes)) {
                $this->_set("parameter_" . count($this->parameters),
                            array(self::$typesTranslations[$type], $value));
                return true;
            }
            return false;
        }

        public function getParameter()
        {
            $value = null;
            if ($this->paramPointer < count($this->parameters)) {
                $value = $this->_get("parameter_".$this->paramPointer);
                $this->paramPointer++;
            }
            return $value;
        }

        public function resetPointer()
        {
            $this->paramPointer = 0;
        }

        public function getParametersCount()
        {
            return count($this->parameters);
        }
    }
