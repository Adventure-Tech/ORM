<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use AdventureTech\ORM\AliasingManagement\LocalAliasingManager;
use stdClass;

readonly class EnumMapper implements Mapper
{
    private string $enumClassName;

    public function __construct(
        private string $name,
        private \ReflectionProperty $property
    ) {
        $this->enumClassName = $this->property->getType()->getName();
    }

    public function serialize(mixed $value): array
    {
        return [$this->name => $value->value ?? null];
    }

    public function deserialize(stdClass $item, LocalAliasingManager $aliasingManager): mixed
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
