<?php

use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\MissingIdForUpdateException;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Facades\DB;

test('Basic insert works with managed datetimes', function () {
    $user = new User();
    $user->name = 'Jane Doe';

    $returnedUser = (new UserPersistence())->insert($user);

    $dbUsers = DB::table('users')->get();

    expect($user)
        ->id->toBeNumeric()
        ->createdAt->not->toBeNull()
        ->updatedAt->not->toBeNull()
    ->and($returnedUser)
        ->id->toBe($user->id)
        ->createdAt->toIso8601String()->toBe($user->createdAt->toIso8601String())
        ->updatedAt->toIso8601String()->toBe($user->updatedAt->toIso8601String())
    ->and($dbUsers)->toHaveCount(1)
    ->and($dbUsers->first())
        ->id->toBe($user->id)
        ->created_at->toBe($user->createdAt->toIso8601String())
        ->updated_at->toBe($user->updatedAt->toIso8601String());
});

test('Basic update works with managed datetimes', function () {
    $user = new User();
    $user->name = 'Jane Doe';

    $persistence = new UserPersistence();

    $persistence->insert($user);

    $user->name = 'John Smith';
    sleep(1);
    $numberOfAffectedRows = $persistence->update($user);

    $dbUsers = DB::table('users')->get();

    expect($user)
        ->id->toBeNumeric()
        ->updatedAt->diffInSeconds($user->createdAt)->toBe(1)
    ->and($numberOfAffectedRows)->toBe(1)
    ->and($dbUsers)->toHaveCount(1)
    ->and($dbUsers->first())
        ->id->toBe($user->id)
        ->name->toBe('John Smith')
        ->updated_at->toBe($user->updatedAt->toIso8601String());
});

test('Basic attaching works')->todo();

test('Soft-deletes work', function () {
});

test('Hard-deletes work', function () {
});

test('Inserting with BelongsTo works', function () {
});

test('Inserting with other relations works', function () {
});

test('Updating with relations works', function () {
});

test('Inserting with ID throws exception', function () {
    $user = new User();
    $user->id = 1;
    $user->name = 'Jane Doe';

    $userPersistence = new UserPersistence();
    expect(fn () => $userPersistence->insert($user))
        ->toThrow(IdSetForInsertException::class);
});

test('Updating without ID throws exception', function () {
    $user = new User();
    $user->name = 'Jane Doe';

    $userPersistence = new UserPersistence();
    expect(fn () => $userPersistence->update($user))
        ->toThrow(MissingIdForUpdateException::class);
});
