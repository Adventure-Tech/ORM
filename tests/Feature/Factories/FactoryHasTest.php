<?php

use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Repository\Repository;
use AdventureTech\ORM\Tests\TestClasses\Entities\Comment;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('Factories can attach to-many relationships with custom factory instances', function (
    string $relation,
    string $reverseRelation,
    string $class,
    string $field,
    array $data,
    int $count
) {
    $factory = Factory::new(User::class)->state(['name' => 'ASD']);
    foreach ($data as $comment) {
        $factory->has($relation, $reverseRelation, Factory::new($class)->state([$field => $comment]));
    }
    $entity = $factory->create();
    expect($entity->{$relation})->toHaveCount(count($data))
        ->pluck($field)->toArray()->toEqual($data)
        ->and(Repository::new($class)->with($reverseRelation)->get()->pluck($reverseRelation . '.id'))->each->toBe($entity->id)
        ->and(Repository::new(User::class)->get()->count())->toBe($count);
})->with([
    ['comments', 'author', Comment::class, 'comment', ['I comment!'], 2], // user + (comment->post->author)
    ['comments', 'author', Comment::class, 'comment', ['I comment!', 'And me too!'], 3], // user + 2 x (comment->post->author)
    //['friends', 'friends', User::class, 'name', ['Alice'], 2], // user + friend
    //['friends', 'friends', User::class, 'name', ['Alice', 'Bob'], 3], // user + 2 x friend
]);

test('Factories can attach to-one relationships with custom factory instances', function (
    string $relation,
    string $reverseRelation,
    string $class,
    string $field,
    string $value,
    int $count
) {
    $entity = Factory::new(User::class)
        ->has($relation, $reverseRelation, Factory::new($class)->state([$field => 'will be overridden']))
        ->has($relation, $reverseRelation, Factory::new($class)->state([$field => $value]))
        ->create();
    expect($entity->{$relation})->toBeInstanceOf($class)
        ->{$field}->toBe($value)
        ->and(Repository::new(User::class)->get())->toHaveCount($count);
})->with([
    ['personalDetails', 'user', PersonalDetails::class, 'email', 'jane@doe.no', 1],
]);

test('Factories can attach to-many relationships with default factory instances', function (
    string $relation,
    string $reverseRelation,
    string $class,
    int $count,
    int $userCount
) {
    $factory = Factory::new(User::class);
    for ($i = 0; $i < $count; $i++) {
        $factory->has($relation, $reverseRelation);
    }
    $entity = $factory->create();
    expect($entity->{$relation})->toHaveCount($count)
        ->and(Repository::new(User::class)->get())->toHaveCount($userCount);
})->with([
    ['comments', 'author', Comment::class, 1, 2], // user + (comment->post->author)
    ['comments', 'author', Comment::class, 2, 3], // 2 x (comment->post->author)
    //['friends', 'friends', User::class, 1, 2], // user + friend
    //['friends', 'friends', User::class, 3, 4], // user + 3 x friend
]);

test('Factories can attach to-one relationships with default factory instances', function (
    string $relation,
    string $reverseRelation,
    string $class,
    string $field,
    int $count
) {
    $entity = Factory::new(User::class)
        ->has($relation, $reverseRelation, Factory::new($class)->state([$field => 'will be overridden']))
        ->has($relation, $reverseRelation, Factory::new($class)->state())
        ->create();
    expect($entity->{$relation})->toBeInstanceOf($class)
        ->{$field}->not->toBe('will be overridden')
        ->and(Repository::new(User::class)->get())->toHaveCount($count);
})->with([
    ['personalDetails', 'user', PersonalDetails::class, 'email', 1],
]);

test('Factory has method guards against invalid relations', function (
    string $relation,
    string $reverseRelations,
    string $message
) {
    expect(fn() => Factory::new(User::class)->has($relation, $reverseRelations))
        ->toThrow(InvalidRelationException::class, $message);
})->with([
    ['invalid', 'user', 'Invalid relation used in "has" method [invalid]'],
    ['comments', 'user', 'Invalid reverse relation used in "has" method [user]'],
]);

test('Factories allow resetting of has method', function () {
    $entity = Factory::new(User::class)
        //->has('friends', 'friends')
        ->has('comments', 'author')
        ->has('comments', 'author')
        ->has('posts', 'author')
        //->without('friends')
        ->without('comments')
        ->without('irrelevant')
        ->has('comments', 'author')
        ->has('comments', 'author')
        ->has('comments', 'author')
        ->create();
    expect(isset($entity->friends))->toBe(false)
        ->and($entity->posts)->toHaveCount(1)
        ->and($entity->comments)->toHaveCount(3)
        ->and(Repository::new(User::class)->get())->toHaveCount(4); // user + 3 x (comment->post->author)
});
