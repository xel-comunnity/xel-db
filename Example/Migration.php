<?php

use Xel\DB\QueryBuilder\Migration\Connection;
use Xel\DB\QueryBuilder\MigrationLoader;
use Xel\DB\QueryBuilder\Migration\MigrationManager;
require __DIR__."/../vendor/autoload.php";

// Specify the directory
$directory = __DIR__ . '/../Example/Migrate/';
$string = "\\Xel\\TEST\Migrate";

try {
    $config = [
        'dsn' => 'mysql:host=localhost;dbname=absensi',
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
    $conn = new Connection($config);
    $load = new MigrationLoader($directory, $string);
    $load->load();

    /**
     * Migration runner
     */
    MigrationManager::init($conn->getConnection(), $load);
    MigrationManager::rollback();

} catch (ReflectionException|Exception $e) {
    echo $e->getMessage();
}

