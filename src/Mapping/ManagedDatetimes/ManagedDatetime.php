<?php

namespace AdventureTech\ORM\Mapping\ManagedDatetimes;

use AdventureTech\ORM\Mapping\Mappers\Mapper;
use AdventureTech\ORM\T;
use stdClass;

interface ManagedDatetime
{
    public function setMapper(Mapper $mapper): void;
    public function getColumnName(): string;
    public function serializeForInsert(object $entity): array;
    public function serializeForUpdate(object $entity): array;
    public function serializeForDelete(object $entity): array;

    /**
     * @param  stdClass  $item
     * @param  string  $alias
     * @return T|null
     */
    public function deserialize(stdClass $item, string $alias): mixed;
}
