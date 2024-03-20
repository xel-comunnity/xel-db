<?php

namespace Xel\DB\QueryBuilder;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;
use Xel\DB\XgenConnector;

readonly class QueryBuilderExecutor
{
    public function __construct(private XgenConnector $connector)
    {}

    /**
     * @throws Exception
     */
    private function getConnection(): false|PDO
    {
        return $this->connector->getConnection();
    }

    /**
     * @param PDO $pdo
     * @return void
     */
    private function closeConnection(PDO $pdo): void
    {
        $this->connector->releaseConnection($pdo);
    }

    /**
     * @param string $query
     * @param array<string|int, mixed> $binding
     * @return false|string|PDOStatement
     * @throws Exception
     */
    public function execute(string $query, array $binding = []): false|string|PDOStatement
    {

        $conn =  $this->getConnection();

        if ($conn === false){
            throw new Exception('Failed to get valid database connection', 500);
        }

        try {
            $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
            $conn->beginTransaction();
            $stmt = $conn->prepare($query);
            foreach ($binding as $item => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
                $stmt->bindValue($item, $value, $paramType);
            }

            $stmt->execute();

            // commit
            $conn->commit();

            // Check if the query was a non-SELECT operation
            $isNonSelect = strtoupper(substr(trim($query), 0, 6)) !== 'SELECT';

            // Return the statement if it's a non-SELECT operation
            if ($isNonSelect) {
                return true; // Or any indication of success
            }

            return $stmt;
        }catch (PDOException $e){
            $conn->rollBack();
            throw new QueryBuilderException($e->getMessage());
        } finally {
            $this->closeConnection($conn);
        }
    }
}