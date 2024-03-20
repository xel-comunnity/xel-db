<?php
namespace Xel\EXAMPLE\Migrate;
use Exception;
use Xel\DB\QueryBuilder\Migration\Migration;
use Xel\DB\QueryBuilder\Migration\Schema;
use Xel\DB\QueryBuilder\Migration\TableBuilder;

class y extends Migration
{
    /**
     * @throws Exception
     */
    public function up(): void
    {
        Schema::create
        ('y',
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
        Schema::drop('y');
    }
};


