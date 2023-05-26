<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\Mapping\Linkers\Linker;
use AdventureTech\ORM\Mapping\Relations\Relation;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 */
class TMP
{
    /**
     * @param  Linker<ORIGIN,TARGET>  $linker
     * @param  Repository<TARGET>  $repository
     */
    public function __construct(
        public Linker $linker,
        public Repository $repository,
    ) {
    }
}
