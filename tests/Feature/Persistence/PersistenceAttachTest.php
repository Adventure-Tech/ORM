<?php

use AdventureTech\ORM\Exceptions\EntityReflectionException;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to attach entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::attach($user, [], 'relation'))->toThrow(
        Error::class,
        'Cannot call abstract method AdventureTech\ORM\Persistence\PersistenceManager::getEntityClassName()'
    );
});

test('Trying to attach to an entity not matching the persistence managers configuration leads to exception', function () {
    $user = new User();
    expect(fn() => PostPersistence::attach($user, [], 'relation'))->toThrow(
        PersistenceException::class,
        'Cannot attach to entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\User" with persistence manager configured for entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post".'
    );
});

test('Trying to attach non-existing relation leads to exception', function () {
    $user = new User();
    expect(fn() => UserPersistence::attach($user, [], 'relation'))->toThrow(
        EntityReflectionException::class,
        'Missing mapping for relation "relation" on "AdventureTech\ORM\Tests\TestClasses\Entities\User". Mapped relations are: "posts", "comments", "personalDetails", "friends".'
    );
});

test('Cannot attach non-many-to-many relation', function () {
    $user = new User();
    expect(fn() => UserPersistence::attach($user, [], 'posts'))->toThrow(
        PersistenceException::class,
        'Can only attach pure many-to-many relations.'
    );
});

test('Entities to be attached must be of correct type', function () {
    $friend = new User();
    $friend->name = 'Alice';
    UserPersistence::insert($friend);
    $user = new User();
    $user->name = 'name';
    UserPersistence::insert($user);
    expect(fn() => UserPersistence::attach($user, [$friend, new Post()], 'friends'))->toThrow(
        PersistenceException::class,
        'Cannot attach entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post" to relation "friends" which links to entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\User".'
    );
});

test('ID must be set on base entity when attaching', function () {
    $user = new User();
    $user->name = 'name';
    expect(fn() => UserPersistence::attach($user, [], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when attaching entities.'
    );
});

test('IDs must be set on entities to be attached', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    expect(fn() => UserPersistence::attach($alice, [$bob], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when attaching entities.'
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

    UserPersistence::attach($alice, [$bob, $claire], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $bob->getIdentifier()],
            ['a_id' => $alice->getIdentifier(), 'b_id' => $claire->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->map(fn(User $user) => $user->getIdentifier())->toArray()->toEqualCanonicalizing([$bob->getIdentifier(), $claire->getIdentifier()]);
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

    UserPersistence::attach($alice, [$bob, $claire], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $bob->getIdentifier()],
            ['a_id' => $alice->getIdentifier(), 'b_id' => $claire->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->map(fn(User $user) => $user->getIdentifier())->toArray()->toEqualCanonicalizing([$bob->getIdentifier(), $claire->getIdentifier()]);
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
    DB::table('friends')->insert(['a_id' => $alice->getIdentifier(), 'b_id' => $alice->getIdentifier()]);

    UserPersistence::attach($alice, [$alice, $bob], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(2)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $alice->getIdentifier()],
            ['a_id' => $alice->getIdentifier(), 'b_id' => $bob->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->map(fn(User $user) => $user->getIdentifier())->toArray()->toEqualCanonicalizing([$alice->getIdentifier(), $bob->getIdentifier()]);
});

test('Trying to attach entities without IDs set leads to exception', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';

    expect(fn() => UserPersistence::attach($alice, [$bob], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when attaching entities.'
    );
});

test('When attaching relations the count of attached entities is returned correctly', function (
    array $friends,
    int $expected
) {
    DB::table('users')-> insert([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
        ['id' => 3, 'name' => 'Claire'],
        ['id' => 4, 'name' => 'Ted'],
    ]);
    $alice = Repository::new(User::class)->findOrFail(1);
    $bob = Repository::new(User::class)->findOrFail(2);
    $claire = Repository::new(User::class)->findOrFail(3);
    $ted = Repository::new(User::class)->findOrFail(4);

    DB::table('friends')->insert($friends);

    $int = UserPersistence::attach($alice, [$bob, $claire, $ted], 'friends');

    expect($int)->toBe($expected);
})->with([
    [[], 3],
    [['a_id' => 1, 'b_id' => 2], 2],
    [[['a_id' => 1, 'b_id' => 2], ['a_id' => 1,'b_id' => 3]], 1],
    [[['a_id' => 1, 'b_id' => 2], ['a_id' => 1,'b_id' => 3], ['a_id' => 1,'b_id' => 4]], 0],
]);
