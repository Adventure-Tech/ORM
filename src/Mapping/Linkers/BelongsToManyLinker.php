<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityReflection;
use Illuminate\Database\Query\Builder;

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
        // TODO: think a
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
     * @return void
     */
    public function join(
        Builder $query,
        string $from,
        string $to
    ): void {
        $originEntityReflection = new EntityReflection($this->originEntity);
        $targetEntityReflection = new EntityReflection($this->targetEntity);
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
                $to . '.' . $targetEntityReflection->getId(),
                '=',
                $pivotTo . '.' . $this->targetForeignKey
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }
}
