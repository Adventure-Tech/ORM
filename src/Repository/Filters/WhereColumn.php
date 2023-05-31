<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class WhereColumn implements Filter
{
    /**
     * @param  string  $column
     * @param  Operator  $operator
     * @param  string  $otherColumn
     */
    public function __construct(private string $column, private Operator $operator, private string $otherColumn)
    {
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
    {
        $query->whereColumn(
            $aliasingManager->getQualifiedColumnName($this->column),
            $this->operator->value,
            $aliasingManager->getQualifiedColumnName($this->otherColumn)
        );
    }
}
