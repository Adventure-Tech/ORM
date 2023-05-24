<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Repository\Repository;
use Attribute;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    private string $table;
    public function __construct(string $table = null, private readonly ?string $repository = null)
    {
        if ($table) {
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
     * @return string
     */
    public function getRepository(): string
    {
        // TODO: where should this live?
        if ($this->repository) {
            return $this->repository;
        } else {
            return Repository::class;
        }
    }
}
