<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Facades\DB;

test('Can create single entity', function () {
    Factory::new(User::class)->create();
    expect(DB::table('users')->count())->toBe(1);
});

test('Non-nullable owning relations are automatically created correctly', function () {
    Factory::new(Post::class)->create();
    expect(DB::table('posts')->count())->toBe(1)
        ->and(DB::table('users')->count())->toBe(1);
});

test('Can set owning relations as instances', function () {
    $editor = Factory::new(User::class)->create();
    Factory::new(Post::class)->create([
        'editor' => $editor
    ]);
    expect(DB::table('posts')->first()->editor)->toBe($editor->getId());
});

test('Can set owning relations as factories', function () {
    $userFactory = Factory::new(User::class);
    Factory::new(Post::class)->create([
        'editor' => $userFactory
    ]);
    $post = DB::table('posts')->first();
    expect(DB::table('posts')->count())->toBe(1)
        ->and(DB::table('users')->count())->toBe(2)
        ->and($post->author)->not->toBe($post->editor);
});

test('Can set state in state method', function () {
    $factory = Factory::new(User::class)->state(['name' => 'Alice']);
    $userA = $factory->create();
    $userB = $factory->create();
    expect($userA->name)->toBe('Alice')
        ->and($userB->name)->toBe('Alice')
        ->and(DB::table('users')->count())->toBe(2)
        ->and(DB::table('users')->get()->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Alice']);
});

test('Can override state in create method', function () {
    $factory = Factory::new(User::class)->state(['name' => 'Alice']);
    $userA = $factory->create();
    $userB = $factory->create(['name' => 'Bob']);
    $userC = $factory->create();
    expect($userA->name)->toBe('Alice')
        ->and($userB->name)->toBe('Bob')
        ->and($userC->name)->toBe('Alice')
        ->and(DB::table('users')->count())->toBe(3)
        ->and(DB::table('users')->get()->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Bob', 'Alice']);
});

test('Can create multiple', function () {
    $users = Factory::new(User::class)->createMultiple(5);
    expect(DB::table('users')->count())->toBe(5)
        ->and($users)->toHaveCount(5);
});

test('Can reuse factories', function () {
    $author = Factory::new(User::class)->create(['name' => 'Alice']);
    $editorFactory = Factory::new(User::class)->state(['name' => 'Bob']);
    $posts = Factory::new(Post::class)->state([
        'author' => $author,
        'editor' => $editorFactory,
    ])->createMultiple(3);

    expect(DB::table('posts')->count())->toBe(3)
        ->and(DB::table('users')->count())->toBe(4)
        ->and(DB::table('users')->where('name', 'Alice')->count())->toBe(1)
        ->and(DB::table('users')->where('name', 'Bob')->count())->toBe(3)
        ->and($posts->map(fn(Post $post) => $post->author->getId())->toArray())->toEqualCanonicalizing([
            $author->getId(),
            $author->getId(),
            $author->getId(),
        ]);
});


########################################################


test('post factory', function () {
    $author = Factory::new(User::class)->create(['name' => 'Alice']);
    $editorFactory = Factory::new(User::class)->state(['name' => 'Bob']);
    $post = Factory::new(Post::class)->create([
        'author' => $author,
        'editor' => $editorFactory,
    ]);
    DB::table('posts')->get()->dump();
    DB::table('users')->get()->dump();
});

test('multiple post factory', function () {
    $author = Factory::new(User::class)->create(['name' => 'Alice']);
    $editorFactory = Factory::new(User::class)->state(['name' => 'Bob']);
    $post = Factory::new(Post::class)->state([
        'author' => $author,
        'editor' => $editorFactory,
    ])->createMultiple(5)->count();
    dump($post);
    DB::table('posts')->get()->dump();
    DB::table('users')->get()->dump();
});
