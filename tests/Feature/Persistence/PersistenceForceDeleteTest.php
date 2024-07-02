<?php

use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PersonalDetailPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to force-delete entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::forceDelete($user))->toThrow(
        Error::class,
        'Cannot call abstract method AdventureTech\ORM\Persistence\PersistenceManager::getEntityClassName()'
    );
});

test('Cannot delete non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::forceDelete($user))->toThrow(
        PersistenceException::class,
        'Cannot delete entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\User" with persistence manager configured for entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post".'
    );
});

test('Can force-delete entity', function () {
    $info = new PersonalDetails();
    $info->email = 'email';
    PersonalDetailPersistence::insert($info);
    PersonalDetailPersistence::forceDelete($info);
    expect(DB::table('personal_details')->get())->toHaveCount(0);
});

test('Can force-delete of entities with soft-deletes', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::forceDelete($user);
    expect(DB::table('users')->get())->toHaveCount(0);
});

test('Trying to force-delete entity without ID set leads exception', function () {
    $user = new User();
    $user->name = 'Name';
    expect(fn() => UserPersistence::forceDelete($user))->toThrow(
        PersistenceException::class,
        'Must set ID column when deleting entities.'
    );
});

test('Trying to force-delete non-existing record leads to exception for entity with soft-deletes', function () {
    $user = new User();
    $user->setIdentifier(1);
    expect(fn() => UserPersistence::forceDelete($user))->toThrow(
        PersistenceException::class,
        'Could not delete all entities. Deleted 0 out of 1.'
    );
});

test('Trying to force-delete non-existing record leads to exception for entity without soft-deletes', function () {
    $personalDetails = new PersonalDetails();
    $personalDetails->id = 1;
    expect(fn() => PersonalDetailPersistence::forceDelete($personalDetails))->toThrow(
        PersistenceException::class,
        'Could not delete all entities. Deleted 0 out of 1.'
    );
});
