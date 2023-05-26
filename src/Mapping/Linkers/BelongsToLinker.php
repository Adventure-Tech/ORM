<?php

namespace AdventureTech\ORM\Mapping\Linkers;

use AdventureTech\ORM\EntityReflection;
use AdventureTech\ORM\Mapping\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 * @implements Relation<ORIGIN,TARGET>
 */

readonly class BelongsToLinker implements Linker
{
    use ToOne;

    /**
     * @param  string  $targetEntity
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
     * @return void
     */
    public function join(
        Builder $query,
        string $from,
        string $to
    ): void {
        $targetEntityReflection = new EntityReflection($this->targetEntity);
        $query
            ->join(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                $to . '.' . $targetEntityReflection->getId(),
                '=',
                $from . '.' . $this->foreignKey
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
