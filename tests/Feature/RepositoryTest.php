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

test('Can find individual record', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $repo = Repository::new(User::class);
    expect($repo->find($id))
        ->toBeInstanceOf(User::class)
        ->id->toBe($id)
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
        ->id->toBe($id)
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

test('Trying to find a filtered-out record results in null', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $filter = new Where('name', IS::NOT_EQUAL, 'Name');
    $repo = Repository::new(User::class)->filter($filter);
    expect($repo->find($id))->toBeNull();
});

test('Trying to find a soft-deleted record results in null', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
    $repo = Repository::new(User::class);
    expect($repo->find($id))->toBeNull();
});

test('Trying to findOrFail a non-existing record results in exception', function () {
    $id = 1;
    $repo = Repository::new(User::class);
    expect(fn() => $repo->findOrFail($id))->toThrow(EntityNotFoundException::class);
});

test('Trying to findOrFail a filtered-out record results in exception', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $filter = new Where('name', IS::NOT_EQUAL, 'Name');
    $repo = Repository::new(User::class)->filter($filter);
    expect(fn() => $repo->findOrFail($id))->toThrow(EntityNotFoundException::class);
});

test('Trying to findOrFail a soft-deleted record results in exception', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
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

test('Can disable soft-delete', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'deleted_at' => now()]);
    $repo = Repository::new(User::class)->includeSoftDeleted();
    expect($repo->findOrFail($id))->toBeInstanceOf(User::class)->id->toBe($id)
        ->and($repo->find($id))->toBeInstanceOf(User::class)->id->toBe($id)
        ->and($repo->get())->pluck('id')->toArray()->toEqualCanonicalizing([$id]);
});

test('Can load relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    $postId = DB::table('posts')->insertGetId(['title' => 'Title', 'content' => 'Content', 'author' => $authorId]);
    $user = Repository::new(User::class)
        ->with('posts')
        ->find($authorId);
    expect($user->posts)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->first()->id->toBe($postId);
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

test('Can load relations within relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'Name']);
    DB::table('posts')->insertGetId(['title' => 'Title', 'content' => 'Content', 'author' => $authorId]);
    $user = Repository::new(User::class)
        ->with('posts', function (PostRepository $repository) {
            $repository->with('author');
        })
        ->find($authorId);
    expect($user->posts->first()->author)
        ->toBeInstanceOf(User::class)
        ->id->toBe($authorId);
});

test('Trying to load invalid relation leads to exception', function () {
    expect(fn() => Repository::new(User::class)->with('invalid'))
        ->toThrow(
            InvalidRelationException::class,
            'Invalid relation used in with clause [tried to load relation "invalid"]'
        );
});

test('Soft-deletes are filtered correctly in loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'FOO']);
    $deletedAuthorId = DB::table('users')->insertGetId(['name' => 'FOO', 'deleted_at' => now()]);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => now()],
        ['id' => 3, 'title' => 'Title', 'content' => 'Content', 'author' => $deletedAuthorId, 'deleted_at' => null],
        ['id' => 4, 'title' => 'Title', 'content' => 'Content', 'author' => $deletedAuthorId, 'deleted_at' => now()],
    ]);
    $users = Repository::new(User::class)
        ->with('posts')
        ->get();
    expect($users)->toHaveCount(1)
        ->first()->id->toBe($authorId)
        ->and($users->first()->posts)->toHaveCount(1)
        ->first()->id->toBe(1);
});

test('Soft-deletes can be deactivated in loaded relations', function () {
    $authorId = DB::table('users')->insertGetId(['name' => 'FOO']);
    $deletedAuthorId = DB::table('users')->insertGetId(['name' => 'FOO', 'deleted_at' => now()]);
    DB::table('posts')->insert([
        ['id' => 1, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => null],
        ['id' => 2, 'title' => 'Title', 'content' => 'Content', 'author' => $authorId, 'deleted_at' => now()],
        ['id' => 3, 'title' => 'Title', 'content' => 'Content', 'author' => $deletedAuthorId, 'deleted_at' => null],
        ['id' => 4, 'title' => 'Title', 'content' => 'Content', 'author' => $deletedAuthorId, 'deleted_at' => now()],
    ]);
    $users = Repository::new(User::class)
        ->with('posts', fn(Repository $repository) => $repository->includeSoftDeleted())
        ->get();
    expect($users)->toHaveCount(1)
        ->first()->id->toBe($authorId)
        ->and($users->first()->posts)->toHaveCount(2)
        ->pluck('id')->toArray()->toEqualCanonicalizing([1,2]);
});
