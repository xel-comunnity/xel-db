<?php

namespace Xel\DB\QueryBuilder\Migration;
abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}