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
    public function __construct(array $config)
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
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}