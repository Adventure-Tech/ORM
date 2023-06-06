<?php

namespace AdventureTech\ORM\Repository\Filters;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

readonly class OrWhere implements Filter
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
        $query->where(function ($query) use ($aliasingManager) {
            foreach ($this->filters as $filter) {
                $query->orWhere(function ($query) use ($filter, $aliasingManager) {
                    $filter->applyFilter($query, $aliasingManager);
                });
            }
        });
    }
}
