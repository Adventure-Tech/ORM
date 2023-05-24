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
class BelongsToMany implements Relation
{
    use ToMany;

    private string $key1;
    private string $key2;
    /**
     * @var class-string<FROM>
     */
    private string $className;

    /**
     * @param  class-string<TO>  $targetEntity
     * @param  string  $pivotTable
     * @param  string|null  $key1
     * @param  string|null  $key2
     */
    public function __construct(
        private readonly string $targetEntity,
        private readonly string $pivotTable,
        string $key1 = null,
        string $key2 = null
    ) {
        // TODO: think about is_null etc
        if (!is_null($key1)) {
            $this->key1 = $key1;
        }
        if (!is_null($key2)) {
            $this->key2 = $key2;
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
        if (!isset($this->key1)) {
            $this->key1 = Str::snake(Str::afterLast($className, '\\')) . '_id';
        }
        if (!isset($this->key2)) {
            $this->key2 = Str::snake(Str::afterLast($this->targetEntity, '\\')) . '_id';
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
        $pivotTo = $to . '_pivot';
        $query
            ->leftJoin(
                $this->pivotTable . ' as ' . $pivotTo,
                $pivotTo . '.' . $this->key1,
                '=',
                $from . '.' . $baseEntityReflection->getId()
            )
            ->leftJoin(
                $targetEntityReflection->getTableName() . ' as ' . $to,
                $to . '.' . $targetEntityReflection->getId(),
                '=',
                $pivotTo . '.' . $this->key2
            )
            ->addSelect($targetEntityReflection->getSelectColumns($to));
    }
}
