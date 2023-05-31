<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class Where implements Filter
{
    /**
     * @param  string  $column
     * @param  Operator  $operator
     * @param  mixed  $value
     */
    public function __construct(private string $column, private Operator $operator, private mixed $value)
    {
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
    {
        $query->where($aliasingManager->getQualifiedColumnName($this->column), $this->operator->value, $this->value);
    }
}
