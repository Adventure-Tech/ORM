<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class WhereIn implements Filter
{
    private readonly mixed $values;

    /**
     * @param  string  $column
     * @param  mixed  $values
     */
    public function __construct(private string $column, mixed $values)
    {
        foreach ($values as $index => $value) {
            if ($value instanceof CarbonInterface) {
                $values[$index] = $value->toIso8601String();
            }
        }
        $this->values = $values;
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager,): void
    {
        $query->whereIn($aliasingManager->getQualifiedColumnName($this->column), $this->values);
    }
}
