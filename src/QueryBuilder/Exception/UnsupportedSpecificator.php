<?php
declare(strict_types=1);

namespace App\QueryBuilder\Exception;

class UnsupportedSpecificator extends QueryBuilderException
{
    public function __construct(string $specificator, int $pos)
    {
        parent::__construct("UnsupportedSpecificator [$specificator] at $pos");
    }
}
