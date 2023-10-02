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
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Linker<ORIGIN,TARGET>
 * @implements PivotLinker<TARGET>
 */

readonly class BelongsToManyLinker implements Linker, PivotLinker
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
        $pivotAlias = $target->getAliasedTableName() . '_pivot';
        $query
            ->leftJoin(
                $this->pivotTable . ' as ' . $pivotAlias,
                $pivotAlias . '.' . $this->originForeignKey,
                '=',
                $origin->getQualifiedColumnName($originEntityReflection->getIdColumn())
            )
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $target->getAliasedTableName(),
                function (JoinClause $join) use ($filters, $pivotAlias, $targetEntityReflection, $target) {
                    $join->on(
                        $target->getQualifiedColumnName($targetEntityReflection->getIdColumn()),
                        $pivotAlias . '.' . $this->targetForeignKey
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
    public function getPivotTable(): string
    {
        return $this->pivotTable;
    }

    /**
     * @return string
     */
    public function getOriginForeignKey(): string
    {
        return $this->originForeignKey;
    }

    /**
     * @return string
     */
    public function getTargetForeignKey(): string
    {
        return $this->targetForeignKey;
    }
}
