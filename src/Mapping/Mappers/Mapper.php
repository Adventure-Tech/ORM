<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use stdClass;

/**
 * @template T
 */
interface Mapper
{
    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array;

    /**
     * @param  mixed  $value
     * @return array<string,string|null>
     */
    public function serialize(mixed $value): array;

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return T|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): mixed;
}
