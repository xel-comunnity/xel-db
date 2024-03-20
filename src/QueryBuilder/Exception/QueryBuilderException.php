<?php

namespace Xel\DB\QueryBuilder\Exception;

use Exception;
use Throwable;

class QueryBuilderException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpCode(): int
    {
        return 422;
    }

    public function getHttpMessage(): string
    {
        return "Unprocessable Content";
    }
}