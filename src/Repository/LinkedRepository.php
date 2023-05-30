<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\Mapping\Linkers\Linker;

/**
 * @template ORIGIN of object
 * @template TARGET of object
 */

readonly class LinkedRepository
{
    /**
     * @param  Linker<ORIGIN,TARGET>  $linker
     * @param  Repository<TARGET>  $repository
     * @param  string  $alias
     */
    public function __construct(
        public Linker $linker,
        public Repository $repository,
        public string $alias,
    ) {
    }
}
