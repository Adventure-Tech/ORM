<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

class UserPersistence extends PersistenceManager
{
    public function __construct()
    {
        parent::__construct(User::class);
    }
}
