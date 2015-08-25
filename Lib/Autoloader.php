<?php

    namespace Scrapper\Lib;

    require "env.php";

    class Autoloader {

        public static function Loader($className)
        {
            $class = self::removeNamespace($className);
            $directories = explode("_", $class);
            $path = ucfirst(implode("/", $directories));
            $path .= ".php";

/*
echo "Autoloader.className: " . $className . "\n";
echo "class: " . $class . "\n";
var_dump(BASE_DIR . "/" . $path);
echo "\n";
*/
            include_once(BASE_DIR . "/" . $path);
        }

        private static function removeNamespace($className)
        {
            $namespaceEnd = strpos($className, "\\");
            $class = $className;
            $i = 0;
            while ($namespaceEnd !== false) {
                $class = substr($class, ($namespaceEnd + 1));
                $namespaceEnd = strpos($class, "\\");
                $i++;
                if ($i > 5) throw new Exception('Invalid class name');
            }

            return $class;
        }
    }

    spl_autoload_register(__NAMESPACE__ . '\Autoloader::Loader');
