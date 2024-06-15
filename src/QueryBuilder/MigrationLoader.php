<?php

namespace Xel\DB\QueryBuilder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;

class MigrationLoader
{
    private array $migrations = [];
    private array $table = [];

    public function __construct(private readonly string $path, private readonly string $namespace)
    {
    }

    /**
     * @throws ReflectionException
     */
    public function load(): void
    {
        $baseDir = realpath($this->path);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = pathinfo($file->getPathname(), PATHINFO_FILENAME);
                $fullClassName = $this->namespace . '\\' . $className;
                if (class_exists($fullClassName) && $className !== "migrations") {
                        $instance = new $fullClassName();
                        $this->migrations[$className] = $instance;
                }else{
                    $instance = new $fullClassName();
                    $this->table[$className] = $instance;
                }
            }
        }

    }
    public function getListOfMigration(): array
    {
        return $this->migrations;
    }

    public function getMigrationTable(): array
    {
        return $this->table;
    }
}