<?php

namespace AdventureTech\ORM\Persistence;

use AdventureTech\ORM\Persistence\Persistors\AttachPersistor;
use AdventureTech\ORM\Persistence\Persistors\DeletePersistor;
use AdventureTech\ORM\Persistence\Persistors\DetachPersistor;
use AdventureTech\ORM\Persistence\Persistors\InsertPersistor;
use AdventureTech\ORM\Persistence\Persistors\RestorePersistor;
use AdventureTech\ORM\Persistence\Persistors\UpdatePersistor;
use Illuminate\Support\Collection;

/**
 * @template TEntity of object
 */
abstract class PersistenceManager
{
    /**
     * @return class-string<TEntity>
     */
    abstract protected static function getEntityClassName(): string;

    final protected function __construct()
    {
    }

    /**
     * @param  TEntity  $entity
     * @return void
     */
    public static function insert(object $entity): void
    {
        static::insertMultiple([$entity]);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @return void
     */
    public static function insertMultiple(iterable $entities): void
    {
        $persistor = new InsertPersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity);
        }
        $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @return void
     */
    public static function update(object $entity): void
    {
        static::updateMultiple([$entity]);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @return void
     */
    public static function updateMultiple(iterable $entities): void
    {
        $persistor = new UpdatePersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity);
        }
        $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @return void
     */
    public static function delete(object $entity): void
    {
        static::deleteMultiple([$entity]);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @return void
     */
    public static function deleteMultiple(iterable $entities): void
    {
        $persistor = new DeletePersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity);
        }
        $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @return void
     */
    public static function restore(object $entity): void
    {
        static::restoreMultiple([$entity]);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @return void
     */
    public static function restoreMultiple(iterable $entities): void
    {
        $persistor = new RestorePersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity);
        }
        $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @return void
     */
    public static function forceDelete(object $entity): void
    {
        static::forceDeleteMultiple([$entity]);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @return void
     */
    public static function forceDeleteMultiple(iterable $entities): void
    {
        $persistor = new DeletePersistor(static::getEntityClassName(), force: true);
        foreach ($entities as $entity) {
            $persistor->add($entity);
        }
        $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function attach(object $entity, Collection|array $linkedEntities, string $relation): int
    {
        return static::attachMultiple([$entity], $linkedEntities, $relation);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function attachMultiple(iterable $entities, Collection|array $linkedEntities, string $relation): int
    {
        $persistor = new AttachPersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity, [$linkedEntities, $relation]);
        }
        return $persistor->persist();
    }

    /**
     * @param  TEntity  $entity
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function detach(object $entity, Collection|array $linkedEntities, string $relation): int
    {
        return static::detachMultiple([$entity], $linkedEntities, $relation);
    }

    /**
     * @param  iterable<int|string,TEntity>  $entities
     * @param  Collection<int|string,object>|array<int|string,object>  $linkedEntities
     * @param  string  $relation
     * @return int
     */
    public static function detachMultiple(iterable $entities, Collection|array $linkedEntities, string $relation): int
    {
        $persistor = new DetachPersistor(static::getEntityClassName());
        foreach ($entities as $entity) {
            $persistor->add($entity, [$linkedEntities, $relation]);
        }
        return $persistor->persist();
    }
}
