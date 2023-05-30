<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Repository\Filters\Filter;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * @template TARGET of object
 * @implements Linker<object,TARGET>
 */

readonly class BelongsToLinker implements Linker
{
    use ToOne;

    /**
     * @param  class-string<TARGET>  $targetEntity
     * @param  string  $relation
     * @param  string  $foreignKey
     */
    public function __construct(
        private string $targetEntity,
        private string $relation,
        private string $foreignKey
    ) {
    }

    /**
     * @return class-string<TARGET>
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    /**
     * @param  Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @param  array<int,Filter>  $filters
     * @return void
     */
    public function join(
        Builder $query,
        string $from,
        string $to,
        array $filters
    ): void {
        $targetEntityReflection = EntityReflection::new($this->targetEntity);
        $query
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                function (JoinClause $join) use ($filters, $from, $targetEntityReflection, $to) {
                    $join->on($to . '.' . $targetEntityReflection->getId(), $from . '.' . $this->foreignKey);
                    foreach ($filters as $filter) {
                        $filter->applyFilter($join, $to);
                    }
                }
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }
}
