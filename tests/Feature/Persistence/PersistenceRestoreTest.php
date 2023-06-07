<?php

use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\CannotRestoreHardDeletedRecordException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PersonalDetailPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to restore entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::restore($user))->toThrow(
        BadlyConfiguredPersistenceManagerException::class,
        'Need to set $entity when extending'
    );
});

test('Cannot restore non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::restore($user))->toThrow(
        InvalidEntityTypeException::class,
        'Invalid entity type used in persistence manager'
    );
});

test('Cannot restore entity without soft-deletes enables', function () {
    $info = new PersonalDetails();
    $info->email = 'email';
    PersonalDetailPersistence::insert($info);
    PersonalDetailPersistence::delete($info);
    expect(fn() => PersonalDetailPersistence::restore($info))->toThrow(
        CannotRestoreHardDeletedRecordException::class,
        'Cannot restore entity without soft-deletes'
    );
});

test('Can restore entity with soft-deletes enabled', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::delete($user);
    UserPersistence::restore($user);
    expect(DB::table('users')->get())
        ->toHaveCount(1)
        ->first()->deleted_at->toBeNull();
});

test('When restoring entity with soft-deletes the deletedAt property is set to null on the object', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::delete($user);
    UserPersistence::restore($user);
    expect($user->deletedAt)->toBeNull();
});

test('Trying to restore entity without ID set leads exception', function () {
    $user = new User();
    $user->name = 'Name';
    expect(fn() => UserPersistence::restore($user))->toThrow(
        MissingIdException::class,
        'Must set ID column when restoring'
    );
});
