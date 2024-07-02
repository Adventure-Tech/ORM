<?php

namespace AdventureTech\ORM\Tests\TestClasses\Persistence;

use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Carbon\CarbonImmutable;

/**
 * @extends PersistenceManager<User>
 */
class UserPersistence extends PersistenceManager
{
    protected static function getEntityClassName(): string
    {
        return User::class;
    }

    public static function customDelete(User $user, CarbonImmutable $deletedAt): void
    {
        (new CustomDelete(self::getEntityClassName(), $deletedAt))->add($user)->persist();
    }
}
