<?php

namespace AdventureTech\ORM\Mapping\Mappers;

use ReflectionNamedType;
use ReflectionProperty;

trait WithDefaultMapperMethods
{
    /**
     * @param  string  $name
     * @param  ReflectionProperty  $property
     */
    public function __construct(
        private string $name,
        private ReflectionProperty $property
    ) {
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }

    /**
     * @param  object  $instance
     * @return bool
     */
    public function isInitialized(object $instance): bool
    {
        return $this->property->isInitialized($instance);
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->property->getName();
    }

    public function getPropertyType(): string
    {
        /** @var ReflectionNamedType $reflectionNamedType */
        $reflectionNamedType = $this->property->getType();
        return $reflectionNamedType->getName();
    }
}
