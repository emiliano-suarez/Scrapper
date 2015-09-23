<?php

    $data = array(
        'sites' => array(
            'AngelList' => array(
                'name' => 'AngelList',
                'ParserClass' => 'Parser_AngelList',
                'setup' => array(
                    'secondsBetweenRequests' => 3,
                ),
                'enabled' => true,
            ),
            'Gust' => array(
                'name' => 'Gust',
                'ParserClass' => 'Parser_Gust',
                'setup' => array(
                    'secondsBetweenRequests' => 5,
                ),
                'enabled' => true,
            ),
            'Dummy' => array(
                'name' => 'Dummy',
                'ParserClass' => 'Parser_Common',
                'setup' => array(
                ),
                'enabled' => false,
            ),
        ),
    );
