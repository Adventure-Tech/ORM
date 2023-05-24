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
class HasMany implements Relation
{
    use ToMany;

    private string $foreignKey;
    /**
     * @var class-string<FROM>
     */
    private string $className;

    /**
     * @param  class-string<TO>  $targetEntity
     * @param  string|null  $foreignKey
     */
    public function __construct(
        private readonly string $targetEntity,
        string $foreignKey = null
    ) {
        if (!is_null($foreignKey)) {
            $this->foreignKey = $foreignKey;
        }
    }


    /**
     * @param  string  $propertyName
     * @param  string  $propertyType
     * @param  class-string<FROM>  $className
     * @return void
     */
    public function initialize(
        string $propertyName,
        string $propertyType,
        string $className,
    ): void {
        $this->className = $className;
        $this->relation = $propertyName;
        if (!isset($this->foreignKey)) {
            $this->foreignKey = Str::snake(Str::afterLast($className, '\\')) . '_id';
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
        $baseEntityReflection = new EntityReflection($this->className);
        $targetEntityReflection = new EntityReflection($this->targetEntity);
        $query
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                $to . '.' . $this->foreignKey,
                '=',
                $from . '.' . $baseEntityReflection->getId()
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }
}
