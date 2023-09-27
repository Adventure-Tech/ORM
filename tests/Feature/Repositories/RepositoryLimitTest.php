<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Benchmark;

test('Limit works correctly', function () {
    $postsPerUser = 5;
    $limit = 2;

    $authors = Factory::new(User::class)->createMultiple(5);
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
    $postsPerUser = 10;
    $limit = 2;

    $authors = Factory::new(User::class)->createMultiple(5);
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
        ->limit($limit)
        ->get();

    expect($users)->toHaveCount($limit)
        ->and($users->map->posts)->each->toHaveCount($postsPerUser);
});
