<?php

namespace Xel\EXAMPLE;

use Exception;
use PDO;
use Xel\DB\QueryBuilder\QueryDML;
use Xel\DB\XgenConnector;

class Bootstrap
{
    public function __construct(private array $config)
    {
    }

    /**
     * @throws Exception
     */
    public function init(): void
    {
        $db = (new XgenConnector([
            'driver' => 'mysql',
            'host' => 'localhost',
            'charset' => 'utf8mb4',
            'username' => 'root',
            'password' => 'Todokana1ko!',
            'dbname' => 'absensi',
            'options' =>[
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        ],
            true, 1
        ));

        $db->initializeConnections();

        $queryBuilder = new QueryDML($db, true);
    }
}