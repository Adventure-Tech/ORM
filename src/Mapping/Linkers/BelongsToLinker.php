<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
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
        $targetEntityReflection = EntityReflection::new($this->targetEntity);
        $query
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $target->getAliasedTableName(),
                function (JoinClause $join) use ($origin, $target, $filters, $targetEntityReflection) {
                    $join->on(
                        $target->getQualifiedColumnName($targetEntityReflection->getId()),
                        $origin->getQualifiedColumnName($this->foreignKey)
                    );
                    foreach ($filters as $filter) {
                        $filter->applyFilter($join, $target);
                    }
                }
            );
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }
}
