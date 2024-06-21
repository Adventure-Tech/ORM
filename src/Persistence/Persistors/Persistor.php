<?php

namespace AdventureTech\ORM\Persistence\Persistors;

/**
 * @template T of object
 */
interface Persistor
{
    /**
     * @param  T  $entity
     * @param  array<int,mixed>  $args
     * @return self<T>
     */
    public function add(object $entity, array $args = null): self;

    /**
     * @return void|mixed
     */
    public function persist();
}
