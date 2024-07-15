<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Select;

/**
 * @extends PersistenceManager<Select>
 */
class SelectPersistence extends PersistenceManager
{
    protected static function getEntityClassName(): string
    {
        return Select::class;
    }
}
