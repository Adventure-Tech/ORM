<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * @template FROM of object
 * @template TO of object
 * @implements Relation<FROM,TO>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo implements Relation
{
    use ToOne;

    private string $foreignKey;
    /**
     * @var class-string<TO>
     */
    private string $targetEntity;

    /**
     * @param  string|null  $foreignKey
     */
    public function __construct(
        string $foreignKey = null
    ) {
        if (!is_null($foreignKey)) {
            $this->foreignKey = $foreignKey;
        }
    }

    /**
     * @param  string  $propertyName
     * @param  class-string<TO>  $propertyType
     * @param  class-string<FROM>  $className
     * @return void
     */
    public function resolveDefault(
        string $propertyName,
        string $propertyType,
        string $className
    ): void {
        $this->targetEntity = $propertyType;
        $this->relation = $propertyName;
        if (!isset($this->foreignKey)) {
            $this->foreignKey = Str::snake($propertyName) . '_id';
        }
    }

    /**
     * @return class-string<TO>
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
}
