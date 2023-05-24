<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\Exceptions\NotInitializedException;
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
    private bool $initialized = false;

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
    public function initialize(string $class): Entity
    {
        $this->initialized = true;
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
        $this->checkInitialized();
        return $this->table;
    }

    /**
     * @return class-string<Repository<T>>|null
     */
    public function getRepository(): ?string
    {
        $this->checkInitialized();
        return $this->repository;
    }

    private function checkInitialized(): void
    {
        if (!$this->initialized) {
            throw new NotInitializedException(self::class);
        }
    }
}
