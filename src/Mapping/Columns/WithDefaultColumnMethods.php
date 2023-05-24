<?php

namespace AdventureTech\ORM\Mapping\Columns;

use AdventureTech\ORM\Exceptions\NotInitializedException;
use Illuminate\Support\Str;
use ReflectionProperty;

trait WithDefaultColumnMethods
{
    private ReflectionProperty $property;
    /**
     * @var string
     */
    private string $name;
    private bool $initialized = false;

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
    public function initialize(ReflectionProperty $property): void
    {
        $this->property = $property;
        if (!isset($this->name)) {
            $this->name = Str::snake($property->getName());
        }
        $this->initialized = true;
    }

    /**
     * @return array<int,string>
     */
    public function getColumnNames(): array
    {
        $this->checkInitialized();
        return [$this->name];
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        $this->checkInitialized();
        return $this->property->getName();
    }

    /**
     * @param  object  $instance
     *
     * @return bool
     */
    public function isInitialized(object $instance): bool
    {
        $this->checkInitialized();
        return $this->property->isInitialized($instance);
    }

    private function checkInitialized(): void
    {
        if (!$this->initialized) {
            throw new NotInitializedException(static::class);
        }
    }
}
