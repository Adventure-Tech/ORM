<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\Exceptions\JSONDeserializationException;
use JsonException;
use stdClass;

/**
 * @implements SimpleMapper<array>
 */

readonly class JSONMapper implements SimpleMapper
{
    use WithDefaultMapperMethods;

    /**
     * @param  array<mixed,mixed>|null  $value
     * @return array<string,string|null>
     * @throws JsonException
     */
    public function serialize(mixed $value): array
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);
        return [$this->name => $json];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return mixed
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): mixed
    {
        $json = json_decode($item->{$aliasingManager->getSelectedColumnName($this->name)}, true);
        if (!is_array($json) && !is_null($json)) {
            throw new JSONDeserializationException();
        }
        return $json;
    }
}
