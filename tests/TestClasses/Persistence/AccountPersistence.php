<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Account;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

class AccountPersistence extends PersistenceManager
{
    protected static string $entity = Account::class;
}
