<?php
declare(strict_types=1);

namespace App\QueryBuilder\Exception;

class ArgumentCountMismatch extends QueryBuilderException
{
    public function __construct(int $provided, int $consumed)
    {
        if ($provided < $consumed) {
            parent::__construct("Not enough arguments");
        } else {
            parent::__construct("Too many arguments");
        }
    }
}
