<?php

use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Filters\IS;
use AdventureTech\ORM\Repository\Filters\WhereNot;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Post;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;
use Illuminate\Support\Collection;

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
    $post = Repository::new(Post::class)
        ->with('author', function (Repository $repository) {
            $repository->filter(new WhereNot('name', IS::LIKE, '%'));
        })
        ->get()
        ->first();

    expect(isset($post->author))->toBeFalse()
        ->and(fn() => $post->author)->toThrow(
            Error::class,
            'Typed property AdventureTech\ORM\Tests\TestClasses\Entities\Post::$author must not be accessed before initialization'
        );
});
