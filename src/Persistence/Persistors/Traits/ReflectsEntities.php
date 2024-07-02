<?php

namespace AdventureTech\ORM\Persistence\Persistors\Traits;

use AdventureTech\ORM\EntityReflection;

/**
 * @template T of object
 */
trait ReflectsEntities
{
    /**
     * @var EntityReflection<T>
     */
    protected readonly EntityReflection $entityReflection;

    /**
     * @param  class-string<T>  $entityClassName
     */
    public function __construct(string $entityClassName)
    {
        $this->entityReflection = EntityReflection::new($entityClassName);
    }
}
