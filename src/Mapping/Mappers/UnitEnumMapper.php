<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\ColumnPropertyService;
use AdventureTech\ORM\Exceptions\EnumSerializationException;
use ReflectionProperty;
use stdClass;
use UnitEnum;

/**
 * @implements Mapper<array>
 */

readonly class UnitEnumMapper implements Mapper
{
    private string $enumClassName;

    /**
     * @param  string  $name
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
        private ReflectionProperty $property
    ) {
        $this->enumClassName = ColumnPropertyService::getPropertyType($this->property);
    }

    /**
     * @param  null|UnitEnum  $value
     * @return array<string,mixed>
     */
    public function serialize(mixed $value): array
    {
        if (!is_null($value) && (!is_object($value) || !enum_exists($value::class))) {
            throw new EnumSerializationException();
        }
        return [$this->name => $value->name ?? null];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return UnitEnum|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): ?UnitEnum
    {
        $value = $item->{$aliasingManager->getSelectedColumnName($this->name)};

        return is_null($value) ? $value : constant($this->enumClassName . '::' . $value);
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }
}
