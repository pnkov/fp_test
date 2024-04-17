<?php

namespace App\FpDbTest;

use App\QueryBuilder\ArgumentStringifier;
use App\QueryBuilder\Builder;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $builder = new Builder($this->mysqli, $query, $args);

        return $builder->build();
    }

    public function skip()
    {
        return ArgumentStringifier::GetSkipValue();
    }
}
