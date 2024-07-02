<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Illuminate\Support\Facades\DB;

test('Inserting multiple', function () {
    $users = [
        Factory::new(User::class)->make(),
        Factory::new(User::class)->make(),
    ];
    UserPersistence::insertMultiple($users);
    expect(DB::table('users')->count())->toBe(2);
});

test('Updating multiple', function () {
    $users = [
        Factory::new(User::class)->create(['name' => 'A']),
        Factory::new(User::class)->create(['name' => 'B']),
    ];
    $users[0]->name = 'NEW';
    $users[1]->name = 'NEW';
    UserPersistence::updateMultiple($users);
    expect(DB::table('users')->where('name', 'NEW')->count())->toBe(2);
});

test('Soft-deleting multiple', function () {
    $users = [
        Factory::new(User::class)->create(),
        Factory::new(User::class)->create(),
    ];
    expect(DB::table('users')->whereNull('deleted_at')->count())->toBe(2);
    UserPersistence::deleteMultiple($users);
    expect(DB::table('users')->whereNotNull('deleted_at')->count())->toBe(2)
        ->and($users[0]->deletedAt)->not->toBeNull()
        ->and($users[1]->deletedAt)->not->toBeNull();
});

test('Force-deleting multiple', function () {
    $users = [
        Factory::new(User::class)->create(),
        Factory::new(User::class)->create(),
    ];
    expect(DB::table('users')->count())->toBe(2);
    UserPersistence::forceDeleteMultiple($users);
    expect(DB::table('users')->count())->toBe(0);
});

test('Restoring multiple', function () {
    $users = [
        Factory::new(User::class)->create(),
        Factory::new(User::class)->create(),
    ];
    UserPersistence::deleteMultiple($users);
    expect(DB::table('users')->whereNotNull('deleted_at')->count())->toBe(2);
    UserPersistence::restoreMultiple($users);
    expect(DB::table('users')->whereNotNull('deleted_at')->count())->toBe(0)
        ->and($users[0]->deletedAt)->toBeNull()
        ->and($users[1]->deletedAt)->toBeNull();
});

test('Attaching multiple many-to-many relations', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);

    UserPersistence::attachMultiple([$alice,$bob], [$bob, $claire], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(4)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $bob->getIdentifier()],
            ['a_id' => $alice->getIdentifier(), 'b_id' => $claire->getIdentifier()],
            ['a_id' => $bob->getIdentifier(), 'b_id' => $claire->getIdentifier()],
            ['a_id' => $bob->getIdentifier(), 'b_id' => $bob->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(2)
        ->and($bob->friends)->toHaveCount(2)
        ->and(isset($claire->friend))->toBeFalse();
});


test('Detaching multiple many-to-many relations', function () {
    $alice = new User();
    $alice->name = 'Alice';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Bob';
    UserPersistence::insert($bob);
    $claire = new User();
    $claire->name = 'Claire';
    UserPersistence::insert($claire);

    UserPersistence::attachMultiple([$alice,$bob, $claire], [$alice, $bob, $claire], 'friends');
    expect(DB::table('friends')->get())->toHaveCount(9);

    UserPersistence::detachMultiple([$alice,$bob], [$bob, $claire], 'friends');

    expect(DB::table('friends')->get())->toHaveCount(5)
        ->map(fn($obj) => (array)$obj)->toArray()->toEqualCanonicalizing([
            ['a_id' => $alice->getIdentifier(), 'b_id' => $alice->getIdentifier()],
            ['a_id' => $bob->getIdentifier(), 'b_id' => $alice->getIdentifier()],
            ['a_id' => $claire->getIdentifier(), 'b_id' => $alice->getIdentifier()],
            ['a_id' => $claire->getIdentifier(), 'b_id' => $bob->getIdentifier()],
            ['a_id' => $claire->getIdentifier(), 'b_id' => $claire->getIdentifier()],
        ])
        ->and($alice->friends)->toHaveCount(1)
        ->and($bob->friends)->toHaveCount(1)
        ->and($claire->friends)->toHaveCount(3);
});
