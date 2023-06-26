<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use stdClass;

/**
 * @template T
 * @implements SimpleMapper<T>
 */

readonly class DefaultMapper implements SimpleMapper
{
    use WithDefaultMapperMethods;

    /**
     * @param  T|null  $value
     * @return array<string,T|null>
     */
    public function serialize(mixed $value): array
    {
        return [$this->name => $value];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return T|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): mixed
    {
        return $item->{$aliasingManager->getSelectedColumnName($this->name)};
    }
}
