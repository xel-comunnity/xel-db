<?php

namespace Xel\DB\QueryBuilder\Migration;
use Exception;
use PDO;
use PDOException;
use Xel\DB\Contract\QueryTableInterface;

class TableBuilder implements QueryTableInterface
{
    private array $field = [];
    private array $alter = [];
    private string $command;
    private string $table;

    public function __construct
    (
        private readonly PDO $pdo
    ){}

    public function create(string $table): TableBuilder
    {
        $this->command = "CREATE TABLE";
        $this->table = $table;
        return $this;
    }

    public function dropTable(string $table): TableBuilder
    {
        $this->command = "DROP TABLE";
        $this->table = $table;
        return $this;
    }

    public function alterTable(string $table): static
    {
        $this->command = "ALTER TABLE";
        $this->table = $table;
        return $this;
    }

    public function truncate(string $table): static
    {
        $this->command = "TRUNCATE TABLE";
        $this->table = $table;
        return $this;
    }

    public function rename(string $table, string $old_name, string $new_name): static
    {
        $this->command = "RENAME TABLE";
        $this->table = "$table $old_name TO $new_name";
        return $this;
    }


    /**
     * @return $this
     * Table maker
     */
    public function id(): static
    {
        $this->field[] = 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
        return $this;
    }

    public function unsignedINT(string $name): static
    {
        $this->field[] = "$name INT UNSIGNED";
        return $this;
    }

    public function string(string $name, int $length = 255): static
    {
        $this->field[] = "$name VARCHAR($length)";
        return $this;
    }

    public function text(string $name): static
    {
        $this->field[] = "$name TEXT";
        return $this;
    }

    public function integer(string $name): static
    {
        $this->field[] = "$name INT";
        return $this;
    }

    public function float(string $name, int $precision = 10, int $scale = 2): static
    {
        $this->field[] = "$name FLOAT($precision, $scale)";
        return $this;
    }

    public function double(string $name, int $precision = 10, int $scale = 2): static
    {
        $this->field[] = "$name DOUBLE($precision, $scale)";
        return $this;
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): static
    {
        $this->field[] = "$name DECIMAL($precision, $scale)";
        return $this;
    }

    // ? DATE
    public function datetime(string $name): static
    {
        $this->field[] = "$name DATETIME)";
        return $this;
    }

    public function date(string $name): static
    {
        $this->field[] = "$name DATE)";
        return $this;
    }

    public function time(string $name): static
    {
        $this->field[] = "$name TIME)";
        return $this;
    }

    public function binary(string $name): static
    {
        $this->field[] = "$name BINARY";
        return $this;
    }

    public function varbinary(string $name): static
    {
        $this->field[] = "$name VARBINARY";
        return $this;
    }

    public function blob(string $name): static
    {
        $this->field[] = "$name BLOB";
        return $this;
    }

    public function point(string $name): static
    {
        $this->field[] = "$name POINT";
        return $this;
    }

    public function default(string $value): static
    {
        $this->field[count($this->field) - 1] .= " DEFAULT $value";
        return $this;
    }

    public function index(string $column, ?string $indexName = null): static
    {
        $indexName = $indexName ?: "index_$column";
        $this->field[] = "INDEX $indexName ($column)";
        return $this;
    }

    public function unique(): static
    {
        $this->field[] = "UNIQUE";
        return $this;
    }

    public function null(): static
    {
        $this->field[] = "NULL";
        return $this;
    }

    public function notNull(): static
    {
        $this->field[] = "NOT NULL";
        return $this;
    }


    public function foreign(string $column, string $foreignTable, string $foreignColumn): static
    {
        $this->field[] = "FOREIGN KEY ($column) REFERENCES $foreignTable($foreignColumn)";
        return $this;
    }

    public function onDelete(string $type): static
    {
        $this->field[] = " ON DELETE $type";
        return $this;
    }

    // Add ON UPDATE support to foreign key constraints
    public function onUpdate(string $type): static
    {
        $this->field[] = " ON UPDATE $type";
        return $this;
    }



    // ? alter field
    public function addColumn(string $name, string $type , mixed $param): static
    {
        $this->alter[] = "ADD COLUMN $name $type".($param ? "($param)" : '');
        return $this;
    }

    public function modifyColumn(string $name, string $type , mixed $param): static
    {
        $this->alter[] = "MODIFY COLUMN $name $type".($param ? "($param)" : '');
        return $this;
    }

    public function dropColumn(string $name): static
    {
        $this->alter[] = "DROP COLUMN $name";
        return $this;
    }

    public function addConstraint(string $constraint): static
    {
        $this->alter[] = "ADD $constraint";
        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alter[] = "DROP CONSTRAINT $constraint";
        return $this;
    }

    public function renameColumn(string $oldName, string $newName, string $type, $parameters = null): static
    {
        $this->alter[] = "CHANGE COLUMN $oldName $newName $type" . ($parameters ? "($parameters)" : '');
        return $this;
    }

    public function renameTable(string $newTableName): static
    {
        $this->alter[] = "RENAME TO $newTableName";
        return $this;
    }


    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $data = implode(", ", $this->field);
        $alter = count($this->alter) > 0 ? implode(",", $this->alter) : null;

        $conn = $this->pdo;
        $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);

        $process = match ($this->command) {
            "CREATE TABLE" => "$this->command IF NOT EXISTS $this->table ($data)",
            "DROP TABLE" => "$this->command IF EXISTS $this->table ",
            "ALTER TABLE" => "$this->command $this->table $alter",
            "TRUNCATE TABLE", "RENAME TABLE" => "$this->command $this->table",
             default => throw new Exception("Incorrect SQL Command"),
        };

        try {
            $conn->exec($process);
        }catch (PDOException|Exception $e){
            throw new Exception($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : null);
        }
    }
}