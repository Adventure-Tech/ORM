<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

class UserPersistence extends PersistenceManager
{
    protected static string $entity = User::class;
}
