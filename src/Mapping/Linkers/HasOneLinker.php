<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\ColumnAliasing\LocalAliasingManager;
use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Repository\Filters\Filter;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Linker<ORIGIN,TARGET>
 */

readonly class HasOneLinker implements Linker
{
    use ToOne;

    /**
     * @param  class-string<ORIGIN>  $originEntity
     * @param  class-string<TARGET>  $targetEntity
     * @param  string  $relation
     * @param  string  $foreignKey
     */
    public function __construct(
        private string $originEntity,
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
     * @param  LocalAliasingManager  $origin
     * @param  LocalAliasingManager  $target
     * @param  array<int,Filter>  $filters
     * @return void
     */
    public function join(
        Builder $query,
        LocalAliasingManager $origin,
        LocalAliasingManager $target,
        array $filters
    ): void {
        $originEntityReflection = EntityReflection::new($this->originEntity);
        $targetEntityReflection = EntityReflection::new($this->targetEntity);
        $query
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $target->getAliasedTableName(),
                function (JoinClause $join) use ($filters, $originEntityReflection, $origin, $target) {
                    $join->on($target->getQualifiedColumnName($this->foreignKey), $origin->getQualifiedColumnName($originEntityReflection->getId()));
                    foreach ($filters as $filter) {
                        $filter->applyFilter($join, $target);
                    }
                }
            );
    }
}
