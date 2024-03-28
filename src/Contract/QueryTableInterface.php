<?php

namespace Xel\DB\Contract;

use Xel\DB\QueryBuilder\Migration\TableBuilder;

interface QueryTableInterface
{
    // ? DDL Operation

    public function create(string $table): TableBuilder;
    public function dropTable(string $table): TableBuilder;
    public function alterTable(string $table): static;
    public function truncate(string $table): static;
    public function rename(string $table, string $old_name, string $new_name): static;

    // ? Default id value
    public function id(): static;

    // ? Text Based

    public function string(string $name, int $length = 255): static;
    public function text(string $name): static;

    // ? Nu,eric
    public function integer(string $name): static;
    public function unsignedINT(string $name): static;
    public function float(string $name, int $precision = 10, int $scale = 2): static;
    public function double(string $name, int $precision = 10, int $scale = 2): static;
    public function decimal(string $name, int $precision = 10, int $scale = 2): static;

    // ? Date format
    public function datetime(string $name): static;
    public function date(string $name): static;
    public function time(string $name): static;

    // ? Other Format
    public function binary(string $name): static;
    public function varbinary(string $name): static;
    public function blob(string $name): static;
    public function point(string $name): static;
    public function default(string $value): static;


    // ? Constraint & Foreign
    public function foreign(string $column, string $foreignTable, string $foreignColumn): static;
    public function onDelete(string $type): static;
    public function onUpdate(string $type): static;
    public function index(string $column, ?string $indexName = null): static;


    // ? alter field
    public function addColumn(string $name, string $type , mixed $param): static;
    public function modifyColumn(string $name, string $type , mixed $param): static;
    public function dropColumn(string $name): static;
    public function addConstraint(string $constraint): static;
    public function dropConstraint(string $constraint): static;
    public function renameColumn(string $oldName, string $newName, string $type, $parameters = null): static;
    public function renameTable(string $newTableName): static;
}