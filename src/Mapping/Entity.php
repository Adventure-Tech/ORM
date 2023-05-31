<?php

namespace AdventureTech\ORM\Mapping;

use AdventureTech\ORM\DefaultNamingService;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Repository;
use Attribute;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Entity
{
    /**
     * @param  string|null  $table
     * @param  class-string<Repository<T>>|null  $repository
     * @param  class-string<Factory<T>>|null  $factory
     */
    public function __construct(
        private ?string $table = null,
        private ?string $repository = null,
        private ?string $factory = null
    ) {
    }

    /**
     * @param  class-string<T>  $class
     * @return string
     */
    public function getTable(string $class): string
    {
        return $this->table ?? DefaultNamingService::tableFromClass($class);
    }

    /**
     * @return class-string<Repository<T>>|null
     */
    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * @return class-string<Factory<T>>|null
     */
    public function getFactory(): ?string
    {
        return $this->factory;
    }
}
