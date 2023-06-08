<?php

use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Exceptions\RecordNotFoundException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to update entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::update($user))->toThrow(
        BadlyConfiguredPersistenceManagerException::class,
        'Need to set $entity when extending'
    );
});

test('Cannot update non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::update($user))->toThrow(
        InvalidEntityTypeException::class,
        'Invalid entity type used in persistence manager'
    );
});

test('Can update entity', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->id = $id;
    $user->name = 'New Name';

    UserPersistence::update($user);

    expect(DB::table('users')->get())
        ->toHaveCount(1)
        ->first()->name->toBe('New Name');
});

test('Trying to update entity without ID set leads exception', function () {
    $user = new User();
    $user->name = 'Name';
    expect(fn() => UserPersistence::update($user))->toThrow(
        MissingIdException::class,
        'Must set ID column when updating'
    );
});

test('Can do partial updates', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'favourite_color' => 'turquoise']);
    $user = new User();
    $user->id = $id;
    $user->favouriteColor = null;
    UserPersistence::update($user);
    expect(DB::table('users')->first())
        ->name->toBe('Name')
        ->favourite_color->toBeNull();
});

test('Managed columns cannot be overridden', function () {
    $createdAt = CarbonImmutable::parse('2023-01-01 12:00')->toIso8601String();
    $updatedAt = CarbonImmutable::parse('2023-01-02 12:00')->toIso8601String();
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'created_at' => $createdAt, 'updated_at' => $updatedAt]);
    $user = new User();
    $user->id = $id;
    $user->name = 'Name';
    $user->updatedAt = null;
    UserPersistence::update($user);
    expect(DB::table('users')->first())
        ->created_at->toBe($createdAt)
        ->updated_at->toBe(now()->toIso8601String());
});

test('When updating entity managed columns are set on the object', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->id = $id;
    $user->name = 'Name';
    UserPersistence::update($user);
    expect($user)
        ->updatedAt->toBeInstanceOf(CarbonImmutable::class);
});

test('Soft-delete columns cannot be overridden', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->id = $id;
    $user->name = 'Name';
    $user->deletedAt = CarbonImmutable::parse('2023-01-01 12:00');
    UserPersistence::update($user);
    expect(DB::table('users')->first())
        ->deleted_at->toBeNull();
});

test('When updating entity soft-delete columns are set to null on the object', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->id = $id;
    $user->name = 'Name';
    UserPersistence::update($user);
    expect($user)->deletedAt->toBeNull();
});

test('Can update owning relations', function () {
    $alice = new User();
    $alice->name = 'Author';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Editor';
    UserPersistence::insert($bob);

    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->author = $alice;
    PostPersistence::insert($post);

    $post->author = $bob;
    PostPersistence::update($post);

    expect(DB::table('posts')->first())
        ->author->toBe($bob->id);
});

test('Must set ID of non-nullable owning relation', function () {
    $alice = new User();
    $alice->name = 'Author';
    UserPersistence::insert($alice);
    $bob = new User();
    $bob->name = 'Editor';

    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->author = $alice;
    PostPersistence::insert($post);

    $post->author = $bob;

    expect(fn() => PostPersistence::update($post))->toThrow(
        MissingIdException::class,
        'Owned linked entity must have valid ID set'
    );
});

test('Can set nullable owning relation to null', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);

    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->author = $user;
    $post->editor = $user;
    PostPersistence::insert($post);

    $post->editor = null;
    PostPersistence::update($post);

    expect(DB::table('posts')->first()->editor)->toBeNull();
});

test('Trying to update non-existing record leads to exception', function () {
    $user = new User();
    $user->id = 1;
    expect(fn() => UserPersistence::delete($user))->toThrow(
        RecordNotFoundException::class,
        'Could not delete entity'
    );
});
