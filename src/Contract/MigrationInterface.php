<?php

namespace Xel\DB\Contract;

interface MigrationInterface
{
    public function up():void;
    public function down():void;
}