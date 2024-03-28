<?php

use Xel\DB\QueryBuilder\QueryBuilderExecutor;
use Xel\DB\XgenConnector;

it("can create connection", function (){
    $result = Swoole\Coroutine\run(/**
     * @throws Exception
     */ function (){
        // ? connection init
        $db = (new XgenConnector([
            'dsn' => 'mysql:host=localhost;dbname=absensi',
            'username' => 'root',
            'password' => 'Todokana1ko!',
            'options' =>[
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        ],
            30,
            20
        ));

        /**
         * Connection db init
         */

        $queryBuilderExecutor = new QueryBuilderExecutor($db);
        $queryBuilder = new Xel\DB\QueryBuilder\QueryDML($queryBuilderExecutor);


        /**
         * Doing Test
         */
        $users = $db->getConnection()->query("SELECT 1");
        $users->execute();
        return $users->fetchAll();


    });

    expect($result)->toBeArray();

});