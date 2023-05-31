<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\ColumnAliasing\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

interface Filter
{
    /**
     * @param  Builder|JoinClause  $query
     * @param  LocalAliasingManager  $aliasingManager
     * @return void
     */
    public function applyFilter(Builder|JoinClause $query, LocalAliasingManager $aliasingManager): void;
}
