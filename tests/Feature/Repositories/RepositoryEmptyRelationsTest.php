<?php

use AdventureTech\ORM\Exceptions\EntityNotFoundException;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\WhereNot;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    Factory::resetFakers(Post::class);
});

test('Loading an empty HasMany relationship', function () {
    Factory::new(Post::class)->createMultiple(5);
    $user = Repository::new(User::class)
        ->with('posts', function (Repository $repository) {
            $repository->filter(new WhereNot('title', IS::LIKE, '%'));
        })
        ->get()
        ->first();

    expect($user->posts)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});

test('Loading an empty BelongsTo relationship', function () {
    Factory::new(Post::class)->create();
    expect(fn () => Repository::new(Post::class)
        ->with('author', function (Repository $repository) {
            $repository->filter(new WhereNot('name', IS::LIKE, '%'));
        })
        ->get()
        ->first())->toThrow(
            EntityNotFoundException::class,
            'Failed to load relation "author" of entity "AdventureTech\ORM\Tests\TestClasses\Entities\Post" with id "1".'
        );
});
