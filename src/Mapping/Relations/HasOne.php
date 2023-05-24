<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne implements Relation
{
    use ToOne;

    private string $targetEntity;
    private string $foreignKey;
    private string $className;

    public function __construct(
        string $foreignKey = null
    ) {
        if ($foreignKey) {
            $this->foreignKey = $foreignKey;
        }
    }


    public function resolveDefault(
        string $propertyName,
        string $propertyType,
        string $className,
    ): void {
        $this->className = $className;
        $this->relation = $propertyName;
        $this->targetEntity = $propertyType;
        if (!isset($this->foreignKey)) {
            $this->foreignKey = Str::snake(Str::afterLast($className, '\\')) . '_id';
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
