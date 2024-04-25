<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;

class PersonalDetailPersistence extends PersistenceManager
{
    protected function getEntityClassName(): string
    {
        return PersonalDetails::class;
    }
}
