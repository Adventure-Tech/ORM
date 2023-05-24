<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Repository\Repository;
use Attribute;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    private string $table;

    /**
     * @param  string|null  $table
     * @param  class-string|null  $repository
     */
    public function __construct(string $table = null, private readonly ?string $repository = null)
    {
        if (!is_null($table)) {
            $this->table = $table;
        }
    }

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
     * @return class-string
     */
    public function getRepository(): string
    {
        // TODO: where should this live?
        return $this->repository ?? Repository::class;
    }
}
