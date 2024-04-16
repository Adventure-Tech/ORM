<?php

use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdValueException;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Exceptions\RecordNotFoundException;
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
        PersistenceException::class,
        'Tried to use entity of type AdventureTech\ORM\Tests\TestClasses\Entities\User in persistence manager configured for entities of type AdventureTech\ORM\Tests\TestClasses\Entities\Post.'
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
    expect($user->deletedAt)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->toIso8601String()->toBe(now()->toIso8601String());
});

test('Can soft-delete entity with custom deleted_at timestamp', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    UserPersistence::customDelete($user, CarbonImmutable::parse('2000-01-01 12:00'));
    expect(DB::table('users')->get())
        ->toHaveCount(1)
        ->first()->deleted_at->toBe('2000-01-01 12:00:00+00');
});

test('Trying to delete entity without ID set leads exception', function () {
    $user = new User();
    $user->name = 'Name';
    expect(fn() => UserPersistence::delete($user))->toThrow(
        PersistenceException::class,
        'Must set ID column when deleting'
    );
});

test('Trying to soft-delete non-existing record leads to exception', function () {
    $user = new User();
    $user->setIdentifier(1);
    expect(fn() => UserPersistence::delete($user))->toThrow(
        RecordNotFoundException::class,
        'Could not delete entity'
    );
});

test('Trying to hard-delete non-existing record leads to exception', function () {
    $personalDetails = new PersonalDetails();
    $personalDetails->id = 1;
    expect(fn() => PersonalDetailPersistence::delete($personalDetails))->toThrow(
        RecordNotFoundException::class,
        'Could not delete entity'
    );
});
