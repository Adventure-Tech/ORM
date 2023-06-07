<?php

use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\Where;
use AdventureTech\ORM\Repository\Filters\WhereColumn;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\PostRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Trying to find a filtered-out record results in null', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $filter = new Where('name', IS::NOT_EQUAL, 'Name');
    $repo = Repository::new(User::class)->filter($filter);
    expect($repo->find($id))->toBeNull();
});

test('Trying to findOrFail a filtered-out record results in exception', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $filter = new Where('name', IS::NOT_EQUAL, 'Name');
    $repo = Repository::new(User::class)->filter($filter);
    expect(fn() => $repo->findOrFail($id))->toThrow(EntityNotFoundException::class);
});

test('Repositories allow filtering of the results', function () {
    DB::table('users')->insert([
        ['name' => 'A'],
        ['name' => 'B'],
    ]);
    $filter = new Where('name', IS::EQUAL, 'A');
    $repo = Repository::new(User::class)->filter($filter);
    expect($repo->get())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->pluck('name')->toArray()->toEqualCanonicalizing(['A']);
});

test('Can filter within loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'Other Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 3, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted' => now()],
     ]);
    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(new Where('title', IS::EQUAL, 'Title'));
        })
        ->find($authorId);
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->posts)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->first()->id->toBe(1);
});

test('Can filter into loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Other Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
    ]);
    $users = Repository::new(User::class)
        ->with('posts')
        ->filter(new Where('posts/title', IS::EQUAL, 'Title'))
        ->get();
    expect($users)->toHaveCount(0);
});

test('Can filter out of loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Other Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
    ]);
    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(new Where('../name', IS::NOT_EQUAL, 'Name'));
        })
        ->find($authorId);
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->posts)->toHaveCount(0);
});

test('Can filter across relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'FOO']);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'FOO', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'BAR', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
    ]);
    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(new WhereColumn('../name', IS::EQUAL, 'title'));
        })
        ->find($authorId);
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->posts)->toHaveCount(1)
        ->first()->id->toBe(1);
});
