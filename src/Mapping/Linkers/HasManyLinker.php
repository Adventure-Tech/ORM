<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Relations\Relation;
use Illuminate\Database\Query\Builder;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Relation<ORIGIN,TARGET>
 */

readonly class HasManyLinker implements Linker
{
    use ToMany;

    /**
     * @param  string  $originEntity
     * @param  string  $targetEntity
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
        $query
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                $to . '.' . $this->foreignKey,
                '=',
                $from . '.' . $originEntityReflection->getId()
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }
}
