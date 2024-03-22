<?php

use Xel\DB\QueryBuilder\Migration\Connection;
use Xel\DB\QueryBuilder\MigrationLoader;
use Xel\DB\QueryBuilder\Migration\MigrationManager;
require __DIR__."/../vendor/autoload.php";

// Specify the directory
$directory = __DIR__ . '/../Example/Migrate/';
$string = "\\Xel\\EXAMPLE\Migrate";

try {
    $config = 'mysql:host=localhost;dbname=databases';
    $dsn = explode(';', $config);
    $dbname = explode('=', $config);
    $config = [
        'dsn' => $config.";charset=utf8mb4",
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ];

    /**
     * Connection Init
     */
    $conn = new Connection($config, $dbname[2]);
    $load = new MigrationLoader($directory, $string);
    $load->load();


   if (!$conn->isDatabaseExists()){
       $conn->createDatabase();
       $conn = 0;
   }
    $x = new Connection($config, $dbname[2]);

    /**
     * Migration runner
     */
    MigrationManager::init($x->getConnection(), $load);
    MigrationManager::rollback(3);
} catch (ReflectionException|Exception $e) {
    echo $e->getMessage();
}

