<?php

namespace Xel\DB\QueryBuilder\Migration;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Xel\DB\QueryBuilder\MigrationLoader;

class MigrationManager
{
    private static  ?PDO $conn = null;
    /**
     * @var array<string|int , mixed>
     */
    private static array $list = [];
    /**
     * @var array<string|int , mixed>
     */
    private static array $table = [];

    public static function init(PDO $conn, MigrationLoader $migrationLoader): void
    {
        self::$conn = $conn;
        self::$list = $migrationLoader->getListOfMigration();
        self::$table = $migrationLoader->getMigrationTable();
    }

    public static function getPDO(): ?PDO
    {
        return self::$conn;
    }

    /**
     * @return array<string|int, mixed>
     */
    private static function fetchInterLockMigration(): array
    {
        /**@var PDO|false $conn*/
        $conn = self::getPDO();
        /**@var PDOStatement|false $stmt*/
        $stmt = $conn->prepare("SELECT migration FROM migrations");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }

    /**
     * @throws Exception
     */
    private static function insertInterLockMigration(string $interlock): void
    {
        try {
            $conn = self::getPDO();
            $conn->beginTransaction();

            // ? check the migration already exist
            $stmt = $conn->prepare("SELECT COUNT(*) FROM migrations WHERE migration = :migration");
            $stmt->bindValue(":migration", $interlock);
            $stmt->execute();

            // ? do nothing if the migration exist
            if (count($stmt->fetchAll()) > 0){
                $conn->commit();
                return;
            }

            // ? insert
            $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
            $stmt->bindValue(":migration", $interlock);
            $stmt->execute();
            $conn->commit();
        }catch (PDOException $e){
            $conn->rollBack();
            throw new Exception($e->getMessage());
        }

    }


    private static function isMigrationExist(): void
    {
        $conn = self::getPDO();
        $stmt = $conn->prepare("SHOW TABLES LIKE 'migrations'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result){
            $migrations = self::$table['migrations'];
            if ($migrations instanceof Migration){
                $migrations->up();
            }
        }
    }

    private static function isTableExist(string $table): mixed
    {
        $conn = self::getPDO();
        $stmt = $conn->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private static function rollbackInformation(): array
    {
        $currentMigration = array_column(self::fetchInterLockMigration(), 'migration');
        $availableMigration = [];

        // detect the available table on migrations
        foreach ($currentMigration as $value){
            $check = self::isTableExist($value);
            if ($check){
                $data = self::$list[$value];
                $availableMigration[] = $data;
            }else{
                $availableMigration = [];
            }
        }

        return $availableMigration;
    }


    /**
     * @throws Exception
     */
    public static function rollback(string|int $step = '*'): void
    {
        $availableMigration = self::rollbackInformation();

        if(is_int($step)){
            try {
                $currentMigration = [];
                // $current Migrations
                foreach (self::$list as $migration){
                    if (count($currentMigration) < $step){
                        $currentMigration[] = $migration;
                    }else{
                        break;
                    }
                }

                if (count($availableMigration) > count($currentMigration)){
                    $currentData = array_diff($availableMigration, $currentMigration);
                    foreach ($currentData as $value){
                        $instance = new $value();
                        if ($instance instanceof Migration){
                            $instance->down();
                        }else{
                            break;
                        }
                    }
                }elseif(count($availableMigration) < count($currentMigration)){
                    $currentData = array_diff($currentMigration, $availableMigration);
                    foreach ($currentData as $value){
                        $instance = new $value();
                        if ($instance instanceof Migration){
                            $instance->up();
                        }else{
                            break;
                        }
                    }
                }else{
                    $currentData = $availableMigration;

                    foreach ($currentData as $value){
                        $instance = new $value();
                        if ($instance instanceof Migration){
                            $instance->up();
                        }else{
                            break;
                        }
                    }
                }
            }  catch (PDOException|Exception $e){
                throw new Exception($e->getMessage());
            }
        }else{
            foreach ($availableMigration as $value){
                $instance = new $value();
                if ($instance instanceof Migration){
                    $instance->down();
                }else{
                    break;
                }
            }
        }
    }


    /**
     * @throws Exception
     */
    public static function migrate(): void
    {
        /**
         * Checkup the Migrations
         */
        self::isMigrationExist();

        foreach (self::$list as $key => $value){
            try {
                if ($value instanceof Migration){
                    $value->up();
                    self::insertInterLockMigration($key);
                }
            }catch (Exception $e){
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function freshMigrate(): void
    {
        /**
         * Checkup the Migrations
         */
        self::isMigrationExist();

        /**
         * Drop Migrations
         */
        self::dropMigration();

        foreach (self::$list as $key => $value){
            try {
                if ($value instanceof Migration){
                    $value->up();
                    self::insertInterLockMigration($key);
                }
            }catch (Exception $e){
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function dropMigration(): void
    {
        foreach (self::$list as $value){
            try {
                if ($value instanceof Migration){
                    $value->down();
                }
            }catch (Exception $e){
                throw new Exception($e->getMessage());
            }
        }

        /**
         * Drop migrations table
         */
        self::$table['migrations']->down();
    }
}


