<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use AdventureTech\ORM\Tests\TestClasses\Factories\PostFactory;

test('BelongsTo relations are resolved correctly', function () {
    $post = Factory::new(Post::class)->create();

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->author)->toBeInstanceOf(User::class);
});

test('Can set custom factories', function () {
    $factory = Factory::new(Post::class);

    expect($factory)->toBeInstanceOf(PostFactory::class);
});

test('Can set state', function () {
    $user = Factory::new(User::class)->create(['name' => 'Jane Doe']);
    $post = Factory::new(Post::class)->create(['author' => $user, 'title' => 'News about the world']);

    expect($post->title)->toBe('News about the world')
        ->and($post->author->id)->toBe($user->id)
        ->and($post->author->name)->toBe('Jane Doe');
});

// TODO: recycle()
// TODO: set relations in state
