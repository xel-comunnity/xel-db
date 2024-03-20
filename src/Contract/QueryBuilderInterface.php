<?php

namespace Xel\DB\Contract;

use Exception;
use Xel\DB\QueryBuilder\QueryBuilder;

interface QueryBuilderInterface
{
    /**
     * @param array<string|int, mixed> $bind
     * @return $this
     */
    public function select(array $bind = ['*']): static;
    public function from(string $table): static;
    public function where(string $column, string $operator, string|int|float $value): static;
    public function andWhere(string $column, string $operator, string|int|float $value): static;
    public function orWhere(string $column, string $operator, string|int|float $value): static;
    public function whereNull(string $column): static;
    public function whereNotNull(string $column): static;
    public function whereBetween(string $column, string|int|float $start, string|int|float $end): static;
    public function whereLike(string $column, string $pattern): static;

    /**
     * @param string $column
     * @param array<string|int, mixed> $values
     * @return $this
     */
    public function whereIn(string $column, array $values): static;
    public function subQuery(string $column, string $operator, callable $subQuery, string $bindValue): static;

    /**
     * @param string $column
     * @param string $operator
     * @param callable $subQuery
     * @param string|array<string|int, mixed> $bindValue
     * @param string $connectOperator
     * @return $this
     */
    public function subQueryCondition
    (
        string $column,
        string $operator,
        callable $subQuery,
        string|array $bindValue,
        string $connectOperator = 'AND'
    ): static;


    /**
     * @throws Exception
     */
    public function latest(int $limit = 0): QueryBuilder;
}