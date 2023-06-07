<?php

use AdventureTech\ORM\Exceptions\AttachingInconsistentEntitiesException;
use AdventureTech\ORM\Exceptions\AttachingToInvalidRelationException;
use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to attach entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::attach($user, Collection::empty(), 'relation'))->toThrow(
        BadlyConfiguredPersistenceManagerException::class,
        'Need to set $entity when extending'
    );
});

test('Trying to attach to an entity not matching the persistence managers configuration leads to exception', function () {
    $user = new User();
    expect(fn() => PostPersistence::attach($user, Collection::empty(), 'relation'))->toThrow(
        InvalidEntityTypeException::class,
        'Invalid entity type used in persistence manager'
    );
});

test('Trying to attach non-existing relation leads to exception', function () {
    $user = new User();
    expect(fn() => UserPersistence::attach($user, Collection::empty(), 'relation'))->toThrow(
        AttachingToInvalidRelationException::class,
        'Can only attach pure many-to-many relations'
    );
});

test('Cannot attach non-many-to-many relation', function () {
    $user = new User();
    expect(fn() => UserPersistence::attach($user, Collection::empty(), 'posts'))->toThrow(
        AttachingToInvalidRelationException::class,
        'Can only attach pure many-to-many relations'
    );
});

test('Entities to be attached must be of correct type', function () {
    $friend = new User();
    $friend->name = 'Alice';
    UserPersistence::insert($friend);
    $user = new User();
    $user->name = 'name';
    UserPersistence::insert($user);
    expect(fn() => UserPersistence::attach($user, collect([$friend, new Post()]), 'friends'))->toThrow(
        AttachingInconsistentEntitiesException::class,
        'All entities in collection must be of correct type'
    );
});

test('ID must be set on base entity when attaching', function () {
    $user = new User();
    $user->name = 'name';
    expect(fn() => UserPersistence::attach($user, Collection::empty(), 'friends'))->toThrow(
        MissingIdException::class,
        'Must set ID column on base entity when attaching'
    );
});

test('IDs must be set on entities to be attached', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    expect(fn() => UserPersistence::attach($alice, collect([$bob]), 'friends'))->toThrow(
        MissingIdException::class,
        'Must set ID columns of all entities when attaching'
    );
});

test('Can attach many-to-many relations', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);

    UserPersistence::attach($alice, collect([$bob, $claire]), 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->id, 'b_id' => $bob->id],
            ['a_id' => $alice->id, 'b_id' => $claire->id],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->pluck('id')->toArray()->toEqualCanonicalizing([$bob->id, $claire->id]);
});

test('Attaching ignores and overwrites relation property', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);

    $alice->friends = collect([$claire]);

    UserPersistence::attach($alice, collect([$bob, $claire]), 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->id, 'b_id' => $bob->id],
            ['a_id' => $alice->id, 'b_id' => $claire->id],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->pluck('id')->toArray()->toEqualCanonicalizing([$bob->id, $claire->id]);
});

test('Attaching handles already existing links correctly', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);
    DB::table('friends')->insert(['a_id' => $alice->id, 'b_id' => $alice->id]);

    UserPersistence::attach($alice, collect([$alice, $bob]), 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->id, 'b_id' => $alice->id],
            ['a_id' => $alice->id, 'b_id' => $bob->id],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->pluck('id')->toArray()->toEqualCanonicalizing([$alice->id, $bob->id]);
});
