<?php

namespace Xel\DB\Contract;

use Exception;
use stdClass;

interface QueryBuilderResultInterface
{

    /**
     * @return array<string|int, mixed>
     * @throws Exception
     */
    public function get(): array;


    /**
     * @return stdClass|array<string|int, mixed>
     */
    public function toObject():stdClass|array;

    /**
     * @throws Exception
     */
    public function toJson(bool $prettyPrint = false): false|string;
    public function orderBy(string $column, string $direction = 'DESC'): static;
}