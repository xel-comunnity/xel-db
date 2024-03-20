<?php

use Xel\DB\QueryBuilder\Migration\Connection;
use Xel\DB\QueryBuilder\MigrationLoader;
use Xel\DB\QueryBuilder\Migration\MigrationManager;


it('it can run migrations', function () {
    $directory = __DIR__ . '/../Example/Migrate';
    $string = "\\Xel\\TEST\Migrate";

    $config = [
        'dsn' => 'mysql:host=localhost;dbname=absensi', // Use the same test database
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ];

    // Rollback the migrations
    $conn = new Connection($config);
    $load = new MigrationLoader($directory, $string);
    $load->load();

    MigrationManager::init($conn->getConnection(), $load);
    MigrationManager::migrate();

    // Check if a specific table exists after running migrations
    $tableExists = $conn->getConnection()->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    $this->assertTrue($tableExists, 'The "users" table should exist after running migrations');

    // Check if a specific column exists in a table after running migrations
    $columnExists = $conn->getConnection()->query("SHOW COLUMNS FROM users LIKE 'email'")->rowCount() > 0;
    $this->assertTrue($columnExists, 'The "email" column should exist in the "users" table after running migrations');

    // Check if data was inserted into a table after running migrations
    $userData = $conn->getConnection()->query("SELECT * FROM users WHERE fullname = 'Member'")->fetch();
    $this->assertNotEmpty($userData, 'User data should be present in the "users" table after running migrations');
});

it('it can run rollback with step', function () {
    $directory = __DIR__ . '/../Example/Migrate';
    $string = "\\Xel\\TEST\Migrate";

    $config = [
        'dsn' => 'mysql:host=localhost;dbname=absensi', // Use the same test database
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ];

    // Rollback the migrations
    $conn = new Connection($config);
    $load = new MigrationLoader($directory, $string);
    $load->load();

    MigrationManager::init($conn->getConnection(), $load);
    MigrationManager::rollback();

    // Check if a specific table exists after running migrations
    $tableExists = $conn->getConnection()->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    $this->assertTrue($tableExists, 'The "users" table should exist after running migrations');

    // Check if a specific column exists in a table after running migrations
    $columnExists = $conn->getConnection()->query("SHOW COLUMNS FROM users LIKE 'email'")->rowCount() > 0;
    $this->assertTrue($columnExists, 'The "email" column should exist in the "users" table after running migrations');

    // Check if data was inserted into a table after running migrations
    $userData = $conn->getConnection()->query("SELECT * FROM users WHERE fullname = 'Member'")->fetch();
    $this->assertNotEmpty($userData, 'User data should be present in the "users" table after running migrations');
});

it('it can run fresh migrate', function () {
    $directory = __DIR__ . '/../Example/Migrate';
    $string = "\\Xel\\TEST\Migrate";

    $config = [
        'dsn' => 'mysql:host=localhost;dbname=absensi', // Use the same test database
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ];

    // Rollback the migrations
    $conn = new Connection($config);
    $load = new MigrationLoader($directory, $string);
    $load->load();

    MigrationManager::init($conn->getConnection(), $load);
    MigrationManager::freshMigrate();

    // Check if a specific table exists after running migrations
    $tableExists = $conn->getConnection()->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    $this->assertTrue($tableExists, 'The "users" table should exist after running migrations');

    // Check if a specific column exists in a table after running migrations
    $columnExists = $conn->getConnection()->query("SHOW COLUMNS FROM users LIKE 'email'")->rowCount() > 0;
    $this->assertTrue($columnExists, 'The "email" column should exist in the "users" table after running migrations');

    // Check if data was inserted into a table after running migrations
    $userData = $conn->getConnection()->query("SELECT * FROM users WHERE fullname = 'Member'")->fetch();
    $this->assertNotEmpty($userData, 'User data should be present in the "users" table after running migrations');
});

it(/**
 * @throws ReflectionException
 */ 'it can run drop migrate', function () {
    $directory = __DIR__ . '/../Example/Migrate';
    $string = "\\Xel\\TEST\Migrate";

    $config = [
        'dsn' => 'mysql:host=localhost;dbname=absensi', // Use the same test database
        'username' => 'root',
        'password' => 'Todokana1ko!',
        'options' =>[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ];

    // Rollback the migrations
    $conn = new Connection($config);
    $load = new MigrationLoader($directory, $string);
    $load->load();

    MigrationManager::init($conn->getConnection(), $load);
    MigrationManager::freshMigrate();

});