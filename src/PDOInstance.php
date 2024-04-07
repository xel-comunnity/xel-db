<?php

namespace Xel\DB;

use Exception;
use PDO;
use PDOException;

class PDOInstance
{
    /**
     * @throws Exception
     */
    public static function create(array $config): PDO
    {
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        try {
            return new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            throw new Exception("Failed to create database connection: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}