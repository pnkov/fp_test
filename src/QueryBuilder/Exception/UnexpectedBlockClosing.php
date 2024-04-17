<?php
declare(strict_types=1);

namespace App\QueryBuilder\Exception;

class UnexpectedBlockClosing extends QueryBuilderException
{
    public function __construct(int $pos)
    {
        parent::__construct("Condition block closed before opened at $pos");
    }
}
