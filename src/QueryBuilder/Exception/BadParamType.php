<?php
declare(strict_types=1);

namespace App\QueryBuilder\Exception;

class BadParamType extends QueryBuilderException
{
    public function __construct(string $type, int $idx)
    {
        parent::__construct("Unsupported parameter type $type at $idx index");
    }
}
