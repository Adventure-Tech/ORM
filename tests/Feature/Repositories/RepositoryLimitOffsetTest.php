<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('Limit works correctly', function () {
    $numberOfUsers = 5;
    $postsPerUser = 5;
    $limit = 2;

    $authors = Factory::new(User::class)->createMultiple($numberOfUsers);
    foreach ($authors as $author) {
        Factory::resetFakers();
        Factory::new(Post::class)
            ->state(['author' => $author])
            ->createMultiple($postsPerUser);
    }

    $users = Repository::new(User::class)
        ->with('posts')
        ->limit($limit)
        ->get();

    expect($users)->toHaveCount($limit)
        ->and($users->map->posts)->each->toHaveCount($postsPerUser);
});

test('Limit is ignored in loaded relationships', function () {
    $numberOfUsers = 5;
    $postsPerUser = 10;
    $limit = 2;

    $authors = Factory::new(User::class)->createMultiple($numberOfUsers);
    foreach ($authors as $author) {
        Factory::resetFakers();
        Factory::new(Post::class)
            ->state(['author' => $author])
            ->createMultiple($postsPerUser);
    }

    $users = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->limit(5);
        })
        ->get();

    expect($users)->toHaveCount($numberOfUsers)
        ->and($users->map->posts)->each->toHaveCount($postsPerUser);
});

test('Offset works correctly', function () {
    $numberOfUsers = 5;
    $postsPerUser = 5;
    $offset = 2;

    $authors = Factory::new(User::class)->createMultiple($numberOfUsers);
    foreach ($authors as $author) {
        Factory::resetFakers();
        Factory::new(Post::class)
            ->state(['author' => $author])
            ->createMultiple($postsPerUser);
    }

    $users = Repository::new(User::class)
        ->with('posts')
        ->offset($offset)
        ->get();

    expect($users)->toHaveCount($numberOfUsers - $offset)
        ->and($users->map->posts)->each->toHaveCount($postsPerUser);
});

test('Offset is ignored in loaded relationships', function () {
    $numberOfUsers = 5;
    $postsPerUser = 10;
    $offset = 2;

    $authors = Factory::new(User::class)->createMultiple($numberOfUsers);
    foreach ($authors as $author) {
        Factory::resetFakers();
        Factory::new(Post::class)
            ->state(['author' => $author])
            ->createMultiple($postsPerUser);
    }

    $users = Repository::new(User::class)
        ->with('posts', function (Repository $repository) use ($offset) {
            $repository->offset($offset);
        })
        ->get();

    expect($users)->toHaveCount($numberOfUsers)
        ->and($users->map->posts)->each->toHaveCount($postsPerUser);
});
