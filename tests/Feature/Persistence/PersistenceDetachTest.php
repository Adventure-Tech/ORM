<?php

use AdventureTech\ORM\Exceptions\EntityReflectionException;
use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to detach entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::detach($user, Collection::empty(), 'relation'))->toThrow(
        Error::class,
        'Cannot call abstract method AdventureTech\ORM\Persistence\PersistenceManager::getEntityClassName()'
    );
});

test('Trying to detach to an entity not matching the persistence managers configuration leads to exception', function () {
    $user = new User();
    expect(fn() => PostPersistence::detach($user, Collection::empty(), 'relation'))->toThrow(
        PersistenceException::class,
        'Cannot detach from entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\User" with persistence manager configured for entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post".'
    );
});

test('Trying to detach non-existing relation leads to exception', function () {
    $user = new User();
    expect(fn() => UserPersistence::detach($user, [], 'relation'))->toThrow(
        EntityReflectionException::class,
        'Missing mapping for relation "relation" on "AdventureTech\ORM\Tests\TestClasses\Entities\User". Mapped relations are: "posts", "comments", "personalDetails", "friends".'
    );
});

test('Cannot detach non-many-to-many relation', function () {
    $user = new User();
    expect(fn() => UserPersistence::detach($user, [], 'posts'))->toThrow(
        PersistenceException::class,
        'Can only detach pure many-to-many relations'
    );
});

test('Entities to be detached must be of correct type', function () {
    $friend = new User();
    $friend->name = 'Alice';
    UserPersistence::insert($friend);
    $user = new User();
    $user->name = 'name';
    UserPersistence::insert($user);
    expect(fn() => UserPersistence::detach($user, [$friend, new Post()], 'friends'))->toThrow(
        PersistenceException::class,
        'Cannot detach entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post" from relation "friends" which links to entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\User".'
    );
});

test('ID must be set on base entity when detaching', function () {
    $user = new User();
    $user->name = 'name';
    expect(fn() => UserPersistence::detach($user, [], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when detaching entities.'
    );
});

test('IDs must be set on entities to be detached', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    expect(fn() => UserPersistence::detach($alice, [$bob], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when detaching entities.'
    );
});

test('Can detach many-to-many relations', function () {
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

    UserPersistence::detach($alice, [$bob], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(1)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $claire->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(1)
        ->map(fn(User $user) => $user->getIdentifier())->toArray()->toEqualCanonicalizing([$claire->getIdentifier()]);
});

test('Attaching removes detached entities from relation property', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);
    $ted = new User();
    UserPersistence::attach($alice, [$bob, $claire], 'friends');

    $alice->friends = collect([$claire, $ted]);

    UserPersistence::detach($alice, [$claire], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(1)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $bob->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(1)
        ->first()->toBe($ted);
});

test('Detaching handles non-existing links correctly', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    DB::table('friends')->insert(['a_id' => $alice->getIdentifier(), 'b_id' => $alice->getIdentifier()]);
    $alice->friends = collect([$alice, $bob]);

    UserPersistence::detach($alice, [$alice, $bob], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(0)
        ->and($alice->friends)->toHaveCount(0);
});

test('Trying to detach entities without IDs set leads to exception', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';

    expect(fn() => UserPersistence::detach($alice, [$bob], 'friends'))->toThrow(
        PersistenceException::class,
        'Must set ID columns when detaching entities.'
    );
});

test('When detaching relations the count of detached entities is returned correctly', function (
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

    $int = UserPersistence::detach($alice, [$bob, $claire, $ted], 'friends');

    expect($int)->toBe($expected);
})->with([
    [[], 0],
    [['a_id' => 1, 'b_id' => 2], 1],
    [[['a_id' => 1, 'b_id' => 2], ['a_id' => 1,'b_id' => 3]], 2],
    [[['a_id' => 1, 'b_id' => 2], ['a_id' => 1,'b_id' => 3], ['a_id' => 1,'b_id' => 4]], 3],
]);
