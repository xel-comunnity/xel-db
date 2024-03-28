<?php

namespace Xel\DB\QueryBuilder\Migration;
use Exception;
use Xel\DB\Contract\QueryDDLInterface;

class Schema extends MigrationManager implements QueryDDLInterface
{
    public static function create(string $tableName, callable $callback): TableBuilder
    {
        $builder = new TableBuilder(static::getPDO());
        $create = $builder->create($tableName);
        $callback($create);
        return $builder;
    }

    /**
     * @throws Exception
     */
    public static function drop(string $tableName): void
    {
        $builder = new TableBuilder(self::getPDO());
        $builder->dropTable($tableName)->execute();
    }

    public static function alter(string $tableName, callable $callback): TableBuilder
    {
        $builder = new TableBuilder(self::getPDO());
        $create = $builder->alterTable($tableName);
        $callback($create);
        return $builder;
    }

    /**
     * @throws Exception
     */
    public static function truncate(string $tableName): void
    {
        $builder = new TableBuilder(self::getPDO());
        $builder->truncate($tableName)->execute();
    }

    /**
     * @throws Exception
     */
    public static function rename(string $tableName, string $old_name, string $new_name): void
    {
        $builder = new TableBuilder(self::getPDO());
        $builder->rename($tableName, $old_name,$new_name)->execute();
    }

}
