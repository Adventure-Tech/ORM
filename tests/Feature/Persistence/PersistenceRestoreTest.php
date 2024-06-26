<?php

use AdventureTech\ORM\Exceptions\PersistenceException;
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
        Error::class,
        'Cannot call abstract method AdventureTech\ORM\Persistence\PersistenceManager::getEntityClassName()'
    );
});

test('Cannot restore non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::restore($user))->toThrow(
        PersistenceException::class,
        'Cannot restore entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\User" with persistence manager configured for entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post".'
    );
});

test('Cannot restore entity without soft-deletes enables', function () {
    $info = new PersonalDetails();
    $info->email = 'email';
    PersonalDetailPersistence::insert($info);
    PersonalDetailPersistence::delete($info);
    expect(fn() => PersonalDetailPersistence::restore($info))->toThrow(
        PersistenceException::class,
        'Cannot restore entity without soft-deletes.'
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
        PersistenceException::class,
        'Must set ID column when restoring entities.'
    );
});

test('Trying to restore non-existing record leads to exception', function () {
    $user = new User();
    $user->setIdentifier(1);
    $user->name = 'I do not exist';
    expect(fn() => UserPersistence::restore($user))->toThrow(
        PersistenceException::class,
        'Could not restore all entities. Restored 0 out of 1.'
    );
});
