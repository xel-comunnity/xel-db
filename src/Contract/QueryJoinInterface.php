<?php

namespace Xel\DB\Contract;

interface QueryJoinInterface
{
    public function innerJoin(string $table, string $condition): static;
    public function leftJoin(string $table, string $condition): static;
    public function rightJoin(string $table, string $condition): static;
    public function crossJoin(string $table): static;
}