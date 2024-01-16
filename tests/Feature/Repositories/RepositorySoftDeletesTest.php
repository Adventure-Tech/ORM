<?php

use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

test('Trying to find a soft-deleted record results in null', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
    $repo = Repository::new(User::class);
    expect($repo->find($id))->toBeNull();
});

test('Trying to findOrFail a soft-deleted record results in exception', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
    $repo = Repository::new(User::class);
    expect(fn() => $repo->findOrFail($id))->toThrow(EntityNotFoundException::class);
});

test('Getting records via the repository ignores soft deletes', function () {
    DB::table('users')->insert([
        ['name' => 'A', 'deleted_at' => now()],
        ['name' => 'B', 'deleted_at' => null],
        ['name' => 'C', 'deleted_at' => null],
    ]);
    $repo = Repository::new(User::class);
    expect($repo->get())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->pluck('name')->toArray()->toEqualCanonicalizing([ 'B', 'C']);
});

test('Can disable soft-delete', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
    $repo = Repository::new(User::class)->includeSoftDeleted();
    expect($repo->findOrFail($id))->toBeInstanceOf(User::class)->getIdentifier()->toBe($id)
        ->and($repo->find($id))->toBeInstanceOf(User::class)->getIdentifier()->toBe($id)
        ->and($repo->get())->map(fn(User $user) => $user->getIdentifier())->toArray()->toEqualCanonicalizing([$id]);
});

test('Soft-deletes are filtered correctly in loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'FOO']);
    $deletedAuthorId = DB::table('users')->insertGetId(['name' => 'FOO', 'deleted_at' => now()]);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $authorId, 'deleted_at' => now()],
        ['id' => 3, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $deletedAuthorId, 'deleted_at' => null],
        ['id' => 4, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $deletedAuthorId, 'deleted_at' => now()],
    ]);
    $users = Repository::new(User::class)
        ->with('posts')
        ->get();
    expect($users)->toHaveCount(1)
        ->first()->getIdentifier()->toBe($authorId)
        ->and($users->first()->posts)->toHaveCount(1)
        ->and($users->first()->posts->first())->id->toBe(1);
});

test('Soft-deletes can be deactivated in loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'FOO']);
    $deletedAuthorId = DB::table('users')->insertGetId(['name' => 'FOO', 'deleted_at' => now()]);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $authorId, 'deleted_at' => now()],
        ['id' => 3, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $deletedAuthorId, 'deleted_at' => null],
        ['id' => 4, 'title' => 'Title', 'content' => 'Content', 'number' => 'ONE', 'author' => $deletedAuthorId, 'deleted_at' => now()],
    ]);
    $users = Repository::new(User::class)
        ->with('posts', fn(Repository $repository) => $repository->includeSoftDeleted())
        ->get();
    expect($users)->toHaveCount(1)
        ->first()->getIdentifier()->toBe($authorId)
        ->and($users->first()->posts)->toHaveCount(2)
        ->pluck('id')->toArray()->toEqualCanonicalizing([1,2]);
});
