<?php

namespace AdventureTech\ORM\Repository\Filters;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

interface Filter
{
    public function applyFilter(Builder|JoinClause $query, string $alias): void;

    /**
     * @return array<int,string>
     */
    public function getRelations(): array;
}
