<?php

    namespace Scrapper\Lib;

    class Lib_Helper {

        const MAX_CURL_RETRIES = 3;
        const SECONDS_BETWEEN_RETRIES = 3;

        public function getPage($url,
                                $fields = null,
                                $headers = array())
        {
            $fields_string = '';

            if (is_array($fields)) {
                foreach ($fields as $key => $value) {
                  $fields_string .= $key.'='.$value.'&';
                }
                rtrim($fields_string, '&');
            }
            else {
                $fields_string = $fields;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if ($fields) {
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            }

            $retries = 0;
            do {
                $response = curl_exec($ch);

                // Split Header and Body from the response
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);

                // Get the first line of the Header and check if it is a valid response
                $headerFisrtLine = strtok($header, "\n");
                $validResponse = strpos($headerFisrtLine, "200 OK") ? true : false;

                if ( ! $validResponse ) {
                    $retries++;
                    sleep($this::SECONDS_BETWEEN_RETRIES);
                }
            }
            while (( ! $validResponse) && $retries < $this::MAX_CURL_RETRIES);
            curl_close($ch);

            if (( ! $validResponse) && ($retries == $this::MAX_CURL_RETRIES)) {
                echo "Cannot load url: {$url}, with fields ({$fields_string})\n";
                return false;
            }
            else {
                $body = substr($response, $headerSize);
                return $body;
            }
        }

        public function write($filename, $txt)
        {
            $file = fopen($filename, "w") or die("Unable to open file!");
            fwrite($file, $txt);
            fclose($file);
        }
    }
