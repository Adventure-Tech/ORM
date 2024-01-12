<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class WhereNot implements Filter
{
    private readonly mixed $value;

    /**
     * @param  string  $column
     * @param  IS  $operator
     * @param  mixed  $value
     */
    public function __construct(private string $column, private IS $operator, mixed $value)
    {
        $this->value = $value instanceof CarbonInterface
            ? $value->toIso8601String()
            : $value;
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
    {
        $query->whereNot($aliasingManager->getQualifiedColumnName($this->column), $this->operator->value, $this->value);
    }
}
