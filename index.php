<?php

    require "Lib/Autoloader.php";

    use Scrapper\Lib as Lib;
    use Scrapper\Controllers as Controllers;

    $config = new Lib\Lib_Config();
    $scrapper = new Controllers\Controllers_Scrapper($config);
    $scrapper->run();
