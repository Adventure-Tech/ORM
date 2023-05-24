<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Illuminate\Support\Str;
use ReflectionProperty;

trait WithDefaultColumnMethods
{
    private ReflectionProperty $property;

    public function __construct(
        private ?string $name = null
    ) {
    }

    public function resolveDefault(ReflectionProperty $property): void
    {
        $this->property = $property;
        if (is_null($this->name)) {
            $this->name = Str::snake($property->getName());
        }
    }

    /**
     * @return array
     */
    public function getColumnNames(): array
    {
        return [$this->name];
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->property->getName();
    }

    /**
     * @param  object  $instance
     *
     * @return bool
     */
    public function isInitialized(object $instance): bool
    {
        return $this->property->isInitialized($instance);
    }
}
