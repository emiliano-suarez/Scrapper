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
            'Dummy' => array(
                'name' => 'Dummy',
                'ParserClass' => 'Parser_Common',
                'setup' => array(
                ),
                'enabled' => true,
            ),
        ),
    );