<?php

namespace AdventureTech\ORM\Mapping\Columns;

use Illuminate\Support\Str;
use ReflectionProperty;

trait WithDefaultColumnMethods
{
    private ReflectionProperty $property;
    /**
     * @var string
     */
    private string $name;

    /**
     * @param  string|null  $name
     */
    public function __construct(
        string $name = null
    ) {
        if (!is_null($name)) {
            $this->name = $name;
        }
    }

    /**
     * @param  ReflectionProperty  $property
     * @return void
     */
    public function resolveDefault(ReflectionProperty $property): void
    {
        $this->property = $property;
        if (!isset($this->name)) {
            $this->name = Str::snake($property->getName());
        }
    }

    /**
     * @return array<int,string>
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
