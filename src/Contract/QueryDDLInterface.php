<?php

namespace Xel\DB\Contract;

use Exception;
use Xel\DB\QueryBuilder\Migration\TableBuilder;

interface QueryDDLInterface
{
    public static function create(string $tableName, callable $callback): TableBuilder;

    /**
     * @throws Exception
     */
    public static function drop(string $tableName): void;

    public static function alter(string $tableName, callable $callback): TableBuilder;

    /**
     * @throws Exception
     */
    public static function truncate(string $tableName): void;
    /**
     * @throws Exception
     */
    public static function rename(string $tableName, string $old_name, string $new_name): void;
}