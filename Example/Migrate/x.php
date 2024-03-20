<?php
namespace Xel\EXAMPLE\Migrate;
use Exception;
use Xel\DB\QueryBuilder\Migration\Migration;
use Xel\DB\QueryBuilder\Migration\Schema;
use Xel\DB\QueryBuilder\Migration\TableBuilder;

class x extends Migration
{
    /**
     * @throws Exception
     */
    public function up(): void
    {
        Schema::create
        ('x',
            function (TableBuilder $tableBuilder
            ){
            $tableBuilder
                ->id()
                ->string('name', 100);
        })->execute();
    }

    /**
     * @throws Exception
     */
    public function down(): void
    {
        Schema::drop('x');
    }
}


