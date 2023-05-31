<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class WhereNotIn implements Filter
{
    /**
     * @param  string  $column
     * @param  mixed  $values
     */
    public function __construct(private string $column, private mixed $values)
    {
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager,): void
    {
        $query->whereNotIn($aliasingManager->getQualifiedColumnName($this->column), $this->values);
    }
}
