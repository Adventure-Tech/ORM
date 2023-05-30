<?php

namespace AdventureTech\ORM\Repository\Filters;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class IsNull implements Filter
{
    /**
     * @param  string  $column
     */
    public function __construct(private string $column)
    {
    }

    public function applyFilter(JoinClause|Builder $query, string $alias): void
    {
        $query->whereNull($alias . '.' . $this->column);
    }

    public function getRelations(): array
    {
        return [];
    }
}
