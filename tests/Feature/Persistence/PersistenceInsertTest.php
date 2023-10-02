<?php

use AdventureTech\ORM\Exceptions\BadlyConfiguredPersistenceManagerException;
use AdventureTech\ORM\Exceptions\IdSetForInsertException;
use AdventureTech\ORM\Exceptions\InvalidEntityTypeException;
use AdventureTech\ORM\Exceptions\MissingIdException;
use AdventureTech\ORM\Exceptions\MissingOwningRelationException;
use AdventureTech\ORM\Exceptions\MissingValueForColumnException;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\IntEnum;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

test('Cannot use base persistence manager to insert entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::insert($user))->toThrow(
        BadlyConfiguredPersistenceManagerException::class,
        'Need to set $entity when extending'
    );
});

test('Cannot insert non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::insert($user))->toThrow(
        InvalidEntityTypeException::class,
        'Invalid entity type used in persistence manager'
    );
});

test('Can insert entity', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    expect(DB::table('users')->get())
        ->toHaveCount(1)
        ->first()->name->toBe('Name');
});

test('When inserting entity the id is set on the object', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    expect($user->getIdentifier())->toBeNumeric();
});

test('Trying to insert entity with ID already set leads exception', function () {
    $user = new User();
    $user->setIdentifier(1);
    $user->name = 'Name';
    expect(fn() => UserPersistence::insert($user))->toThrow(
        IdSetForInsertException::class,
        'Must not set ID column for insert'
    );
});

test('Trying to insert entity with missing column leads exception', function () {
    $user = new User();
    expect(fn() => UserPersistence::insert($user))->toThrow(
        MissingValueForColumnException::class,
        'Forgot to set non-nullable property "name"'
    );
});

test('Managed columns cannot be overridden', function () {
    $user = new User();
    $user->name = 'Name';
    $user->createdAt = CarbonImmutable::parse('2023-01-01 12:00');
    $user->updatedAt = null;
    UserPersistence::insert($user);
    expect(DB::table('users')->first())
        ->created_at->toBe(now()->toIso8601String())
        ->updated_at->toBe(now()->toIso8601String());
});

test('When inserting entity managed columns are set on the object', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    expect($user)
        ->createdAt->toBeInstanceOf(CarbonImmutable::class)
        ->updatedAt->toBeInstanceOf(CarbonImmutable::class);
});

test('Soft-delete columns cannot be overridden', function () {
    $user = new User();
    $user->name = 'Name';
    $user->deletedAt = CarbonImmutable::parse('2023-01-01 12:00');
    UserPersistence::insert($user);
    expect(DB::table('users')->first())
        ->deleted_at->toBeNull();
});

test('When inserting entity soft-delete columns are set to null on the object', function () {
    $user = new User();
    $user->name = 'Name';
    UserPersistence::insert($user);
    expect($user)
        ->deletedAt->toBeNull();
});

test('Can insert owning relations', function () {
    $author = new User();
    $author->name = 'Author';
    UserPersistence::insert($author);
    $editor = new User();
    $editor->name = 'Editor';
    UserPersistence::insert($editor);

    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->author = $author;
    $post->editor = $editor;
    $post->number = IntEnum::ONE;

    PostPersistence::insert($post);

    expect(DB::table('posts')->first())
        ->author->toBe($author->getIdentifier())
        ->editor->toBe($editor->getIdentifier());
});

test('Must set owning relation', function () {
    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->number = IntEnum::ONE;

    expect(fn() => PostPersistence::insert($post))->toThrow(
        MissingOwningRelationException::class,
        'Must set all non-nullable owning relations'
    );
});

test('Must set ID of owning relation', function () {
    $post = new Post();
    $post->title = 'Title';
    $post->content = 'Content';
    $post->number = IntEnum::ONE;
    $post->author = new User();

    expect(fn() => PostPersistence::insert($post))->toThrow(
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
    $post->number = IntEnum::ONE;
    $post->author = $user;
    $post->editor = null;

    PostPersistence::insert($post);

    expect(DB::table('posts')->first()->editor)->toBeNull();
});
