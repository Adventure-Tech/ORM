<?php

namespace AdventureTech\ORM\Repository\Filters;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use LogicException;

class DefaultFilter implements Filter
{
    /**
     * @var array<int,string>
     */
    private array $relations;
    private Closure $callable;

    public static function where(string $column): FilterWhere
    {
        return new FilterWhere($column);
    }

    /**
     * @param  DefaultFilter  ...$filters
     * @return DefaultFilter
     */
    public static function or(...$filters): DefaultFilter
    {
        $orFilter = new self('dummy', Operator::EQUAL, 'data', true);
        $orFilter->callable = function (Builder $query, string $alias) use ($filters) {
            $query->where(function (Builder $query) use ($alias, $filters) {
                foreach ($filters as $filter) {
                    $query->orWhere(fn ($q) => $filter->applyFilter($q, $alias));
                }
            });
        };
        return $orFilter;
    }

    /**
     * @param  DefaultFilter  ...$filters
     * @return DefaultFilter
     */
    public static function and(...$filters): DefaultFilter
    {
        $orFilter = new self('dummy', Operator::EQUAL, 'data', true);
        $orFilter->callable = function (Builder $query, string $alias) use ($filters) {
            foreach ($filters as $filter) {
                $query->where(fn ($query) => $filter->applyFilter($query, $alias));
            }
        };
        return $orFilter;
    }

    public function __construct(
        string $column,
        Operator $operator,
        mixed $value,
        bool $columnCompare
    ) {
        $relations = explode('.', $column);
        // TODO: check that this is not null!
        $column = array_pop($relations);
        $this->relations = array_reverse($relations);

        if ($columnCompare) {
            if (!is_string($value)) {
                throw new LogicException('Column names must be strings');
            }
            $this->callable = function (Builder $query, string $alias) use ($value, $operator, $column) {
                $query->whereColumn($alias . '.' . $column, $operator->value, $alias . '.' . $value);
            };
        } else {
            $this->callable = function (Builder $query, string $alias) use ($value, $operator, $column) {
                $query->where($alias . '.' . $column, $operator->value, $value);
            };
        }
    }

    public function applyFilter(Builder|JoinClause $query, string $alias): void
    {
        ($this->callable)($query, $alias);
    }

    /**
     * @return array<int,string>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }
}
