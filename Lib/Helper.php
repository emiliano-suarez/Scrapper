<?php

    namespace Scrapper\Lib;

    class Lib_Helper {

        public function getPage($url,
                                $fields = array(),
                                $headers = array())
        {
            $fields_string = '';
            
            foreach ($fields as $key => $value) {
                $fields_string .= $key.'='.$value.'&';
            }
            rtrim($fields_string, '&');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            if ($fields) {
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            }

            $result = curl_exec($ch);
            curl_close($ch);
            
            return $result;
        }
        
        public function write($filename, $txt)
        {
            $file = fopen($filename, "w") or die("Unable to open file!");
            fwrite($file, $txt);
            fclose($file);
        }
    }
