<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class AndWhere implements Filter
{
    /**
     * @var array<int|string,Filter>
     */
    private array $filters;

    /**
     * @param  Filter  ...$filters
     */
    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }

    public function applyFilter(JoinClause|Builder $query, LocalAliasingManager $aliasingManager): void
    {
        foreach ($this->filters as $filter) {
            $filter->applyFilter($query, $aliasingManager);
        }
    }
}
