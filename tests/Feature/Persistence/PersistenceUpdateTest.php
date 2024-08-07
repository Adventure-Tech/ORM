<?php

use AdventureTech\ORM\Exceptions\PersistenceException;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Persistence\PersistenceManager;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestCase;
use AdventureTech\ORM\Tests\TestClasses\BackedEnum;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\Select;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Persistence\PostPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\SelectPersistence;
use AdventureTech\ORM\Tests\TestClasses\Persistence\UserPersistence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('Cannot use base persistence manager to update entities', function () {
    $user = new User();
    expect(fn() => PersistenceManager::update($user))->toThrow(
        Error::class,
        'Cannot call abstract method AdventureTech\ORM\Persistence\PersistenceManager::getEntityClassName()'
    );
});

test('Cannot update non-matching entity', function () {
    $user = new User();
    expect(fn() => PostPersistence::update($user))->toThrow(
        PersistenceException::class,
        'Cannot update entity of type "AdventureTech\ORM\Tests\TestClasses\Entities\User" with persistence manager configured for entities of type "AdventureTech\ORM\Tests\TestClasses\Entities\Post".'
    );
});

test('Can update entity', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->setIdentifier($id);
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
        PersistenceException::class,
        'Must set ID column when updating entities.'
    );
});

test('Attempting partial updates throws exception', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'favourite_color' => 'turquoise']);
    $user = new User();
    $user->setIdentifier($id);
    $user->favouriteColor = null;
    expect(fn() => UserPersistence::update($user))->toThrow(
        PersistenceException::class,
        'Must set non-nullable property "name".'
    );
});

test('Managed columns cannot be overridden', function () {
    $createdAt = CarbonImmutable::parse('2023-01-01 12:00')->format(TestCase::DATETIME_FORMAT);
    $updatedAt = CarbonImmutable::parse('2023-01-02 12:00')->format(TestCase::DATETIME_FORMAT);
    $id = DB::table('users')->insertGetId(['name' => 'Name', 'created_at' => $createdAt, 'updated_at' => $updatedAt]);
    $user = new User();
    $user->setIdentifier($id);
    $user->name = 'Name';
    $user->updatedAt = null;
    UserPersistence::update($user);
    expect(DB::table('users')->first())
        ->created_at->toStartWith($createdAt)
        ->updated_at->toStartWith(now()->format(TestCase::DATETIME_FORMAT));
});

test('When updating entity managed columns are set on the object', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->setIdentifier($id);
    $user->name = 'Name';
    UserPersistence::update($user);
    expect($user)
        ->updatedAt->toBeInstanceOf(CarbonImmutable::class);
});

test('Soft-delete columns cannot be overridden', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->setIdentifier($id);
    $user->name = 'Name';
    $user->deletedAt = CarbonImmutable::parse('2023-01-01 12:00');
    UserPersistence::update($user);
    expect(DB::table('users')->first())
        ->deleted_at->toBeNull();
});

test('When updating entity soft-delete columns are set to null on the object', function () {
    $id = DB::table('users')->insertGetId(['name' => 'Name']);
    $user = new User();
    $user->setIdentifier($id);
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
    $post->number = BackedEnum::ONE;
    PostPersistence::insert($post);

    $post->author = $bob;
    PostPersistence::update($post);

    expect(DB::table('posts')->first())
        ->author->toBe($bob->getIdentifier());
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
    $post->number = BackedEnum::ONE;
    PostPersistence::insert($post);

    $post->author = $bob;

    expect(fn() => PostPersistence::update($post))->toThrow(
        PersistenceException::class,
        'Owned linked entity must have valid ID set.'
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
    $post->number = BackedEnum::ONE;
    PostPersistence::insert($post);

    $post->editor = null;
    PostPersistence::update($post);

    expect(DB::table('posts')->first()->editor)->toBeNull();
});

test('Trying to update non-existing record leads to exception', function () {
    $user = new User();
    $user->setIdentifier(1);
    $user->name = 'Foo';
    expect(fn() => UserPersistence::update($user))->toThrow(
        PersistenceException::class,
        'Could not update all entities. Updated 0 out of 1.'
    );
});

test('Updating nullable column to null', function () {
    $user = Factory::new(User::class)->create(['favouriteColor' => 'red']);
    $user->favouriteColor = null;

    UserPersistence::update($user);

    $user = Repository::new(User::class)->get()->first();
    expect($user->favouriteColor)->toBeNull();
});

test('Updating skips soft-deleted entities', function () {
    $user = Factory::new(User::class)->create(['name' => 'OLD']);
    $userSoftDeleted = Factory::new(User::class)->create(['name' => 'OLD']);
    UserPersistence::delete($userSoftDeleted);
    $user->name = 'NEW';
    $userSoftDeleted->name = 'NEW';
    expect(fn() => UserPersistence::updateMultiple([$user, $userSoftDeleted]))->toThrow(
        PersistenceException::class,
        'Could not update all entities. Updated 1 out of 2.'
    )
        ->and(DB::table('users')->where('name', 'NEW')->count())->toBe(1);
});

test('updating works with table and column names equal to SQL keywords', function () {
    DB::table('select')->insert([
        'id' => 1,
        'end' => Str::ulid(),
    ]);
    $ulid = Str::ulid();
    $entity = new Select();
    $entity->id = 1;
    $entity->end = $ulid;

    SelectPersistence::update($entity);

    $this->assertDatabaseHas('select', [
        'id' => 1,
        'end' => $ulid,
    ]);
});
