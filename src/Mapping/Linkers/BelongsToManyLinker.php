<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Repository\Filters\Filter;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Linker<ORIGIN,TARGET>
 */

readonly class BelongsToManyLinker implements Linker
{
    use ToMany;

    /**
     * @param  class-string<ORIGIN>  $originEntity
     * @param  class-string<TARGET>  $targetEntity
     * @param  string  $relation
     * @param  string  $pivotTable
     * @param  string  $originForeignKey
     * @param  string  $targetForeignKey
     */
    public function __construct(
        private string $originEntity,
        private string $targetEntity,
        private string $relation,
        private string $pivotTable,
        private string $originForeignKey,
        private string $targetForeignKey,
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
        $originEntityReflection = EntityReflection::new($this->originEntity);
        $targetEntityReflection = EntityReflection::new($this->targetEntity);
        $pivotTo = $to . '_pivot';
        $query
            ->leftJoin(
                $this->pivotTable . ' as ' . $pivotTo,
                $pivotTo . '.' . $this->originForeignKey,
                '=',
                $from . '.' . $originEntityReflection->getId()
            )
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                function (JoinClause $join) use ($filters, $pivotTo, $targetEntityReflection, $to) {
                    $join->on($to . '.' . $targetEntityReflection->getId(), $pivotTo . '.' . $this->targetForeignKey);
                    foreach ($filters as $filter) {
                        $filter->applyFilter($join, $to);
                    }
                }
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }
}
