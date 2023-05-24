<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Repository\Repository;
use Attribute;
use Illuminate\Support\Str;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    private string $table;

    /**
     * @param  string|null  $table
     * @param  class-string<Repository<T>>|null  $repository
     */
    public function __construct(string $table = null, private readonly ?string $repository = null)
    {
        if (!is_null($table)) {
            $this->table = $table;
        }
    }

    /**
     * @param  class-string<T>  $class
     * @return $this
     */
    public function resolveDefaults(string $class): Entity
    {
        if (!isset($this->table)) {
            $this->table = Str::snake(Str::plural(Str::afterLast($class, '\\')));
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return class-string<Repository<T>>|null
     */
    public function getRepository(): ?string
    {
        // TODO: where should this live?
        return $this->repository;
    }
}
