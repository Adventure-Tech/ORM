<?php

namespace AdventureTech\ORM\Persistence;

use Illuminate\Support\Collection;

/**
 * @template T of object
 */
abstract class PersistenceManager extends BasePersistenceManager
{
    /**
     * @param  T  $entity
     *
     * @return T
     */
    public function insert(object $entity): object
    {
        return parent::insert($entity);
    }

    /**
     * @template key of string|int
     * @param  Collection<key,T>  $entities
     *
     * @return Collection<key,T>
     */
    public function insertMultiple(Collection $entities): Collection
    {
        return $entities->map(fn ($entity) => self::insert($entity));
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function update(object $entity): int
    {
        return parent::update($entity);
    }

    /**
     * @param  T  $entity
     *
     * @return int
     */
    public function delete(object $entity): int
    {
        return parent::delete($entity);
    }

//    public function attach()
//    {
//    }
}
