<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class WhereNull implements Filter
{
    /**
     * @param  string  $column
     */
    public function __construct(private string $column)
    {
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
    {
        $query->whereNull($aliasingManager->getQualifiedColumnName($this->column));
    }
}
