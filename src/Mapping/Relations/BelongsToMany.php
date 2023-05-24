<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsToMany implements Relation
{
    use ToMany;

    private string $key1;
    private string $key2;
    private string $className;

    public function __construct(
        private readonly string $targetEntity,
        private readonly string $pivotTable,
        string $key1 = null,
        string $key2 = null
    ) {
        if ($key1) {
            $this->key1 = $key1;
        }
        if ($key2) {
            $this->key2 = $key2;
        }
    }

    public function resolveDefault(
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

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

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
