<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Filters\DefaultFilter;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('basic value filtering', function () {
    $post = Factory::new(Post::class)->create(['title' => 'TEST']);
    Factory::new(Post::class)->create(['title' => 'NOT TEST']);

    $posts = Repository::new(Post::class)
        ->filter(DefaultFilter::where('title')->equals()->value('TEST'))
        ->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->id)->toBe($post->id);
});

test('basic column filtering', function () {
    $post = Factory::new(Post::class)->create(['title' => 'TEST', 'content' => 'TEST']);
    Factory::new(Post::class)->create(['title' => 'TEST', 'content' => 'NOT TEST']);

    $posts = Repository::new(Post::class)
        ->filter(DefaultFilter::where('title')->equals()->column('content'))
        ->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->id)->toBe($post->id);
});

test('column filtering via relations', function () {
    $alice = Factory::new(User::class)->create(['name' => 'Alice']);
    $bob = Factory::new(User::class)->create(['name' => 'Bob']);

    Factory::new(Post::class)->create(['author' => $alice, 'title' => 'TEST', 'content' => 'NOT TEST']);
    Factory::new(Post::class)->create(['author' => $bob, 'title' => 'TEST', 'content' => 'NOT TEST']);
    $post = Factory::new(Post::class)->create(['author' => $alice, 'title' => 'TEST', 'content' => 'TEST']);

    $posts = Repository::new(Post::class)
        ->filter(DefaultFilter::where('author.name')->equals()->column('author.title'))
        ->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->id)->toBe($post->id);
});

test('filtering in sub-repositories', function () {
    $user = Factory::new(User::class)->create();

    Factory::new(Post::class)->create(['title' => 'A', 'author' => $user]);
    Factory::new(Post::class)->create(['title' => 'B', 'author' => $user]);
    $post = Factory::new(Post::class)->create(['title' => 'C', 'author' => $user]);

    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(DefaultFilter::where('title')->equals()->value('C'));
        })
        ->find($user->id);
    expect($user->posts)->toHaveCount(1)
        ->and($user->posts->first()->id)->toBe($post->id);

    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(DefaultFilter::where('title')->equals()->value('D'));
        })
        ->find($user->id);
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->posts)->toBeEmpty();
});

test('filtering via relations', function () {
    $alice = Factory::new(User::class)->create(['name' => 'Alice']);
    $bob = Factory::new(User::class)->create(['name' => 'Bob']);

    Factory::new(Post::class)->create(['author' => $alice]);
    Factory::new(Post::class)->create(['author' => $alice]);
    $post = Factory::new(Post::class)->create(['author' => $bob]);

    $posts = Repository::new(Post::class)
        ->filter(DefaultFilter::where('author.name')->equals()->value('Bob'))
        ->get();

    expect($posts)->toHaveCount(1)
        ->and($posts->first()->id)->toBe($post->id);
});