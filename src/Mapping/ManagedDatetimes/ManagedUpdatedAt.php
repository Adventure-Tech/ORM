<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use AdventureTech\ORM\Mapping\Mappers\Mapper;
use Carbon\CarbonImmutable;
use stdClass;

class ManagedUpdatedAt implements ManagedDatetime
{
    private Mapper $mapper;
    private string $columnName;

    public function setMapper(Mapper $mapper): void
    {
        $this->mapper = $mapper;
        $this->columnName = $this->mapper->getColumnNames()[0];
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function serializeForInsert(object $entity): array
    {
        $now = CarbonImmutable::now();
        $entity->{$this->mapper->getPropertyName()} = $now;
        return $this->mapper->serialize($entity);
    }

    public function serializeForUpdate(object $entity): array
    {
        $now = CarbonImmutable::now();
        $entity->{$this->mapper->getPropertyName()} = $now;
        return $this->mapper->serialize($entity);
    }

    public function serializeForDelete(object $entity): array
    {
        $now = CarbonImmutable::now();
        $entity->{$this->mapper->getPropertyName()} = $now;
        return $this->mapper->serialize($entity);
    }

    public function deserialize(stdClass $item, string $alias): mixed
    {
        return $this->mapper->deserialize($item, $alias);
    }
}
