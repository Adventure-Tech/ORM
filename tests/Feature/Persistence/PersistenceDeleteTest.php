<?php

use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PersonalDetailPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to delete entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::delete($user))->toThrow(
        BadlyConfiguredPersistenceManagerException::class,
        'Need to set $entity when extending'
    );
});

test('Cannot delete non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::delete($user))->toThrow(
        InvalidEntityTypeException::class,
        'Invalid entity type used in persistence manager'
    );
});

test('Can delete entity', function () {
    $info = new PersonalDetails();
    $info->email = 'email';
    PersonalDetailPersistence::insert($info);
    PersonalDetailPersistence::delete($info);
    expect(DB::table('personal_details')->get())->toHaveCount(0);
});

test('Deleting entities with soft-deletes enabled works correctly', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::delete($user);
    expect(DB::table('users')->get())
        ->toHaveCount(1)
        ->first()->deleted_at->not->toBeNull();
});

test('When deleting entity with soft-deletes the deletedAt property is set on the object', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::delete($user);
    UserPersistence::delete($user);
    expect($user->deletedAt)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe(now()->toIso8601String());
});

test('Trying to delete entity without ID set leads exception', function () {
    $user = new User();
    $user->name = 'Name';
    expect(fn() => UserPersistence::delete($user))->toThrow(
        MissingIdException::class,
        'Must set ID column when deleting'
    );
});
