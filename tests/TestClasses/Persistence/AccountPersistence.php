<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Account;

class AccountPersistence extends PersistenceManager
{
    protected function getEntityClassName(): string
    {
        return Account::class;
    }
}
