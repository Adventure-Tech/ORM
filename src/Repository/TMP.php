<?php

namespace AdventureTech\ORM\Repository;

use AdventureTech\ORM\Mapping\Relations\Relation;

class TMP
{
    public function __construct(
        public Relation $relationInstance,
        public Repository $repository,
    ) {
    }
}
