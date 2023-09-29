<?php

use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Can find individual record', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $repo = Repository::new(User::class);
    expect($repo->find($id))
        ->toBeInstanceOf(User::class)
        ->getId()->toBe($id)
        ->name->toBe('Name')
        ->createdAt->toBeNull()
        ->udpatedAt->toBeNull()
        ->deletedAt->toBeNull();
});

test('Can findOrFail individual record', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $repo = Repository::new(User::class);
    expect($repo->findOrFail($id))
        ->toBeInstanceOf(User::class)
        ->getId()->toBe($id)
        ->name->toBe('Name')
        ->createdAt->toBeNull()
        ->udpatedAt->toBeNull()
        ->deletedAt->toBeNull();
});

test('Trying to find a non-existing record results in null', function () {
    $id = 1;
    $repo = Repository::new(User::class);
    expect($repo->find($id))->toBeNull();
});

test('Trying to findOrFail a non-existing record results in exception', function () {
    $id = 1;
    $repo = Repository::new(User::class);
    expect(fn() => $repo->findOrFail($id))->toThrow(EntityNotFoundException::class);
});

test('Repositories can get multiple records', function () {
    DB::table('users')->insert([
        ['name' => 'A'],
        ['name' => 'B'],
        ['name' => 'C'],
    ]);
    $repo = Repository::new(User::class);
    expect($repo->get())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->pluck('name')->toArray()->toEqualCanonicalizing(['A', 'B', 'C']);
});

test('Repositories can get first of multiple records', function () {
    DB::table('users')->insert([
        ['name' => 'A'],
        ['name' => 'B'],
        ['name' => 'C'],
    ]);
    $repo = Repository::new(User::class);
    expect($repo->first())
        ->toBeInstanceOf(User::class)
        ->name->toBe('A');
});

test('Repositories can get firstOrFail of multiple records', function () {
    DB::table('users')->insert([
        ['name' => 'A'],
        ['name' => 'B'],
        ['name' => 'C'],
    ]);
    $repo = Repository::new(User::class);
    expect($repo->firstOrFail())
        ->toBeInstanceOf(User::class)
        ->name->toBe('A');
});


test('Trying get the first of a non-existing record set results in null', function () {
    $repo = Repository::new(User::class);
    expect($repo->first())->toBeNull();
});

test('Trying get the firstOrFail of a non-existing record set results in exception', function () {
    $repo = Repository::new(User::class);
    expect(fn() => $repo->firstOrFail())->toThrow(EntityNotFoundException::class);
});
