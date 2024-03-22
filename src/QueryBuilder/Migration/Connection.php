<?php

namespace Xel\DB\QueryBuilder\Migration;

use Exception;
use PDO;
use PDOException;

class Connection
{
    private PDO $pdo;

    /**
     * @param array<string|int, mixed> $config
     * @throws Exception
     */
    public function __construct(private readonly array $config, private readonly string $dbname)
    {
        try {
            $this->pdo = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options'],
            );
            $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        }catch (PDOException $e){

        }
    }

    public function isDatabaseExists(): bool
    {

        $stmt = $this->pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db");
        $stmt->execute([':db' => $this->dbname]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createDatabase(): bool
    {
        if (!$this->isDatabaseExists()){
            $sql = "CREATE DATABASE `{$this->dbname}`";
            $this->pdo->exec($sql);
            return true;
        }
        return false;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}