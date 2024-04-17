<?php
declare(strict_types=1);

namespace App\QueryBuilder\Exception;

class UnexpectedBlockOpening extends QueryBuilderException
{
    public function __construct(int $pos)
    {
        parent::__construct("Nested condition blocks does not supported at $pos");
    }
}
