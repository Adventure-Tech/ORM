<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
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
