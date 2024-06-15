<?php

namespace Xel\DB\QueryBuilder;

use Exception;
use PDO;
use PDOException;
use Swoole\Database\PDOProxy;
use Xel\DB\QueryBuilder\Exception\QueryBuilderException;

trait QueryBuilderExecutor
{
    public int $lastId;
    /**
     * @throws Exception
     */
    private function getConnection(): PDO|PDOProxy
    {
        return $this->connector->get();
    }

    /**
     * @param PDO|PDOProxy $pdo
     * @return void
     */
    private function releaseConnection(PDO|PDOProxy $pdo): void
    {
        $this->connector->put($pdo);
    }


    /**
     * @param string $query
     * @param array<string|int, mixed> $binding
     * @return bool|array
     * @throws QueryBuilderException
     * @throws Exception
     */
    public function execute(string $query, array $binding = []): bool|array
    {
        var_dump($query, $binding);
        $conn =  $this->getConnection();
        try {
            $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
            $conn->beginTransaction();
            $stmt = $conn->prepare($query);
            foreach ($binding as $item => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : PDO::PARAM_STR);
                $stmt->bindValue($item, $value, $paramType);
            }
            $stmt->execute();
            $lastInsertedId = $conn->lastInsertId();

            if($lastInsertedId !== false){
                $this->lastId = $lastInsertedId;
            }
            // commit
            $conn->commit();

            // Check if the query was a non-SELECT operation
            $isNonSelect = strtoupper(substr(trim($query), 0, 6)) !== 'SELECT';

            // Return the statement if it's a non-SELECT operation
            if ($isNonSelect) {
                return true; // Or any indication of success
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $e){
            $conn->rollBack();
            throw new QueryBuilderException($e->getMessage());
        } finally {
            $this->releaseConnection($conn);
        }
    }
}