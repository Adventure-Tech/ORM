<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\Where;
use AdventureTech\ORM\Repository\Filters\WhereColumn;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('When not loaded a relation property is not set', function () {
    $post = Factory::new(Post::class)->create();

    $post = Repository::new(Post::class)
        ->find($post->id);

    expect($post)->toHaveProperty('author')
        ->and(isset($post->author))->toBeFalse();
});

test('When loaded a relation property is set correctly', function () {
    $post = Factory::new(Post::class)->create();

    $post = Repository::new(Post::class)
        ->with('author')
        ->find($post->id);

    expect($post)->toHaveProperty('author')
        ->and(isset($post->author))->toBeTrue()
        ->and($post->author)->toBeInstanceOf(User::class);
});

test('Can load nested relations correctly', function () {
    $post = Factory::new(Post::class)->create();

    $post = Repository::new(Post::class)
        ->with('author', function (Repository $repository) {
            $repository->with('posts');
        })
        ->dump()
        ->find($post->id);

    expect($post->author)->toHaveProperty('posts')
        ->and(isset($post->author->posts))->toBeTrue()
        ->and($post->author->posts)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($post->author->posts)->toHaveCount(1)
        ->and($post->author->posts->first()->id)->toBe($post->id);
});
