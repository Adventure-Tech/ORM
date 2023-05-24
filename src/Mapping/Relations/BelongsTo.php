<?php

namespace AdventureTech\ORM\Mapping\Relations;

use AdventureTech\ORM\EntityReflection;
use Attribute;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo implements Relation
{
    use ToOne;

    private string $foreignKey;
    private string $targetEntity;

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
        string $className
    ): void {
        $this->targetEntity = $propertyType;
        $this->relation = $propertyName;
        if (!isset($this->foreignKey)) {
            $this->foreignKey = Str::snake($propertyName) . '_id';
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
