<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\Mapping\Relations\Relation;

/**
 * @template FROM of object
 * @template TO of object
 */
class TMP
{
    /**
     * @param  Relation<FROM,TO>  $relationInstance
     * @param  Repository<TO>  $repository
     */
    public function __construct(
        public Relation $relationInstance,
        public Repository $repository,
    ) {
    }
}
