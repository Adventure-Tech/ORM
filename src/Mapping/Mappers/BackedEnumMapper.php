<?php

/**
 *
 */

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use AdventureTech\ORM\ColumnPropertyService;
use AdventureTech\ORM\Exceptions\EnumSerializationException;
use BackedEnum;
use ReflectionProperty;
use stdClass;

/**
 * @implements Mapper<null|BackedEnum>
 */

readonly class BackedEnumMapper implements Mapper
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
     * @param  mixed  $value
     * @return array<string,mixed>
     */
    public function serialize(mixed $value): array
    {
        if (!is_null($value) && !($value instanceof BackedEnum)) {
            throw new EnumSerializationException();
        }
        return [$this->name => $value->value ?? null];
    }

    /**
     * @param  stdClass  $item
     * @param  LocalAliasingManager  $aliasingManager
     * @return BackedEnum|null
     */
    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): ?BackedEnum
    {
        $value = $item->{$aliasingManager->getSelectedColumnName($this->name)};

        return is_null($value) ? $value : $this->enumClassName::from($value);
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }
}
