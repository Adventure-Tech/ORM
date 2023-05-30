<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('BelongsTo relations are resolved correctly', function () {
    $factory = Factory::new(Post::class);
    $post = $factory->create();

    expect($post)->toBeInstanceOf(Post::class)
    ->and($post->author)->toBeInstanceOf(User::class);
});

//test('Can set custom factories', function () {
//    $factory = Factory::new(Post::class);
//    $post = $factory->create();
//
//    expect($post)->toBeInstanceOf(Post::class)
//    ->and($post->author)->toBeInstanceOf(User::class);
//});

test('Can set state', function () {
    $factory = Factory::new(Post::class);
    $user = Factory::new(User::class)->create(['name' => 'Jane Doe']);
    $post = $factory->create(['author' => $user, 'title' => 'News about the world']);

    expect($post->title)->toBe('News about the world')
        ->and($post->author->id)->toBe($user->id)
        ->and($post->author->name)->toBe('Jane Doe');
});

// TODO: recycle()
// TODO: set relations in state
