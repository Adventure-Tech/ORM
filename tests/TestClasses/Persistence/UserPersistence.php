<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Carbon\CarbonImmutable;

class UserPersistence extends PersistenceManager
{
    protected static string $entity = User::class;

    public static function customDelete(User $user, CarbonImmutable $deletedAt): void
    {
        self::internalDelete($user, $deletedAt);
    }
}
