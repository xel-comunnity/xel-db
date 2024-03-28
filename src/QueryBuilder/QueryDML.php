<?php

namespace Xel\DB\QueryBuilder;
use Exception;
use PDO;
use PDOStatement;
use stdClass;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Xel\DB\Contract\QueryDMLInterface;
use Xel\DB\Contract\QueryBuilderResultInterface;
use Xel\DB\Contract\QueryJoinInterface;
use Xel\DB\XgenConnector;

class QueryDML implements QueryDMLInterface, QueryBuilderResultInterface, QueryJoinInterface
{
    protected string $query;
    /**
     * @var array<string|int, mixed>
     */
    protected array $binding = [];

    protected string|false $result;

    use QueryBuilderExecutor;

    public function __construct
    (
        protected XgenConnector $connector,
        protected bool $mode
    ){}

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getBinding(): array
    {
        return $this->binding;
    }

    public function select(array $bind = ['*']): static
    {
        $this->query = "SELECT ".implode(", ", $bind);
        return $this;
    }

    public function insert(string $table, array $bind): static
    {
        $column = implode(',', array_keys($bind));
        $placeholders= implode(',', array_map(static function ($key) {
            return ':'.$key;
        }, array_keys($bind)));

        $this->query = "INSERT INTO  $table ($column) VALUES ($placeholders)";

        foreach ($bind as $item => $value){
            $this->binding[":$item"] = $value;
        }
        return $this;
    }

    /**
     * @param string $table
     * @param array $bind
     * @return $this
     */
    public function update(string $table, array $bind): static
    {
        $setClause = implode(', ', array_map(
            function ($column){
                return "$column = :$column";
            },array_keys($bind)
        ));

        $this->query = "UPDATE $table SET $setClause";

        foreach ($bind as $item => $value){
            $this->binding[":$item"] = $value;
        }
        return $this;
    }

    public function delete(string $table): static
    {
        $this->query = "DELETE FROM $table";
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function from(string $table): static
    {
        $this->query .= " FROM $table";
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param string|int|float $value
     * @return $this
     */
    public function where(string $column, string $operator, string|int|float $value): static
    {
        $placeholder = ':' . $column;
        $this->query .= " WHERE $column $operator $placeholder";
        $this->binding[$placeholder] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param string|int|float $value
     * @return $this
     */
    public function andWhere(string $column, string $operator, string|int|float $value): static
    {
        $placeholder = ':' . $column;
        $this->query .= " AND $column $operator $placeholder";
        $this->binding[$placeholder] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param string|int|float $value
     * @return $this
     */
    public function orWhere(string $column, string $operator, string|int|float $value): static
    {
        $placeholder = ':' . $column;
        $this->query .= " OR $column $operator $placeholder";
        $this->binding[$placeholder] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function whereNull(string $column): static
    {
        $this->query .= " AND $column IS NULL";
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function whereNotNull(string $column): static
    {
        $this->query .= " AND $column IS NOT NULL";
        return $this;
    }

    /**
     * @param string $column
     * @param string|int|float $start
     * @param string|int|float $end
     * @return $this
     */

    public function whereBetween(string $column, string|int|float $start, string|int|float $end): static
    {
        $this->query .= " WHERE $column :$start AND :$end";
        $this->binding[":$start"] = $start;
        $this->binding[":$end"] = $end;
        return $this;
    }

    /**
     * @param string $column
     * @param string $pattern
     * @return $this
     */
    public function whereLike(string $column, string $pattern): static
    {
        $placeholder = ':' . $column;
        $this->query .= " WHERE $column LIKE $pattern";
        $this->binding[$placeholder] = "%$pattern%";
        return $this;
    }

    /**
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(', ', array_map(function ($index) use ($column) {
            return ":{$column}_$index";
        }, array_keys($values)));

        $this->query .= " WHERE $column IN ($placeholders)";

        foreach ($values as $index => $value) {
            $param = ":{$column}_$index";
            $this->binding[$param] = $value;
        }
        return $this;
    }

    /***
     * @param string $column
     * @param string $operator
     * @param callable $subQuery
     * @param string $bindValue
     * @return $this
     */
    public function subQuery(string $column, string $operator, callable $subQuery, string $bindValue): static
    {
        $query = $subQuery();
        $placeholder = ':' . $column;
        // Store sub query separately
        $subQueries = " WHERE $column $operator ($query)";
        $this->query .= $subQueries;
        $this->binding[$placeholder] = $bindValue;
        return $this;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param callable $subQuery
     * @param string|array<string|int, mixed> $bindValue
     * @param string $connectOperator
     * @return $this
     */
    public function subQueryCondition(string $column, string $operator, callable $subQuery, string|array $bindValue, string $connectOperator = 'AND'): static
    {

        $query = $subQuery();

        if (is_string($bindValue)){
            $placeholder = ':' . $column;
            $subQueries = " $connectOperator $column $operator ($query)";
            $this->query .= $subQueries;
            $this->binding[$placeholder] = $bindValue;

            return $this;
        }

        $this->query .= " $connectOperator $column $operator ($query)";

        foreach ($bindValue as $index => $value) {
            $param = ":{$column}_$index";
            $this->binding[$param] = $value;
        }
        return $this;
    }

    public function innerJoin(string $table, string $condition): static
    {
        $this->query .= " INNER JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): static
    {
        $this->query .= " LEFT JOIN $table ON $condition";
        return $this;
    }

    public function rightJoin(string $table, string $condition): static
    {
        $this->query .= " RIGHT JOIN $table ON $condition";
        return $this;
    }

    public function crossJoin(string $table): static
    {
        $this->query .= " CROSS JOIN $table";
        return $this;
    }

    /**
     * @throws Exception
     */
    public function latest(int $limit = 0): QueryDML
    {
        if ($limit > 0){
            $this->query .= " ORDER BY id DESC LIMIT :limit";
            $this->binding[':limit'] = $limit;
        } else {
            $this->query .= " ORDER BY id DESC";
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    private function executor(): false|string|PDOStatement
    {
        return $this->execute($this->getQuery(), $this->getBinding());
    }

    /**
     * @throws Exception
     */
    public function run(): false|PDO|string|PDOStatement
    {
        $result = $this->execute($this->getQuery(), $this->getBinding());
        $this->resetState();
        return $result;
    }


    /**
     * @return array<string|int, mixed>
     * @throws Exception
     */
    public function get(): array
    {
        return $this->executor()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAsync():?array
    {
        $can = new Channel(1);
        Coroutine::create(function () use($can){
            $data = $this->execute($this->getQuery(), $this->getBinding());
            $result = $data->fetchAll(PDO::FETCH_ASSOC);
            $can->push($result);
        });
        return $can->pop();
    }

    public function resetState(): void
    {
        $this->query = '';
        $this->binding = [];
    }

    /**
     * @throws Exception
     */
    public function toObject(): array|stdClass
    {
        return $this->executor()->fetchAll(PDO::FETCH_OBJ);
    }

    public function toJson(bool $prettyPrint = false): false|string
    {
        $result = $this->executor()->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }
    public function orderBy(string $column, string $direction = 'DESC'): static
    {
        $this->query .= " ORDER BY $column $direction";
        return $this;
    }
}
