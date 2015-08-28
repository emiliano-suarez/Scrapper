<?php

    require "Lib/Autoloader.php";

    use Scrapper\Lib as Lib;
    use Scrapper\Controllers as Controllers;

    $params = array();

    // Check if there are Sites sent by param
    $options = getopt("s:f:");

    if (isset($options["f"])) {
        // Generate a CSV file
        $siteName = $options["f"];
        $csv = new Lib\Lib_Csv($siteName);
        $csv->generate();
    }
    else {
        if (isset($options["s"])) {
            $sitesArray = explode(',', $options["s"]);
            $params['sites'] = $sitesArray;
        }
        $config = new Lib\Lib_Config($params);
        $scrapper = new Controllers\Controllers_Scrapper($config);
        $scrapper->run();
    }
