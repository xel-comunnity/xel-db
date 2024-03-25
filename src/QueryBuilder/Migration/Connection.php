<?php

namespace Xel\DB\QueryBuilder\Migration;

use Exception;
use PDO;
use PDOException;

class Connection
{
    private ?PDO $pdo;
    private readonly array $config;

    /**
     * @param array<string|int, mixed> $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        try {
           $this->init($this->config);
        }catch (PDOException $e){
            throw new Exception($e->getMessage());
        }
    }

    public function init(array $config): PDO
    {
        $dsn = "{$config['driver']}:host={$config['host']};charset={$config['charset']}";
        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'],
        );
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
        $this->pdo = $pdo;
        return $pdo;

    }

    public function isDatabaseExists(): bool
    {
        $stmt = $this->pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db");
        $stmt->execute([':db' => $this->config['dbname']]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createDatabase(): bool
    {
        if (!$this->isDatabaseExists()){
            $sql = "CREATE DATABASE `{$this->config['dbname']}`";
            $this->pdo->exec($sql);
            return true;
        }
        return false;
    }

    public function getConnection(): PDO
    {
        if ($this->isDatabaseExists()){
            $this->pdo = null;

            $dsn = "{$this->config['driver']}:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options'],
            );
            $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
            $this->pdo = $pdo;
        }
        return $this->pdo;
    }
}