<?php

use AdventureTech\ORM\Exceptions\InvalidRelationException;
use AdventureTech\ORM\Factories\Factory;
use AdventureTech\ORM\Tests\TestClasses\Entities\Comment;
use AdventureTech\ORM\Tests\TestClasses\Entities\PersonalDetails;
use AdventureTech\ORM\Tests\TestClasses\Entities\User;

test('Factories can attach to-many relationships with custom factory instances', function (string $relation, string $class, string $field, array $data) {
    $factory = Factory::new(User::class);
    foreach ($data as $comment) {
        $factory->has($relation, Factory::new($class)->state([$field => $comment]));
    }
    $entity = $factory->create();
    expect($entity->{$relation})->toHaveCount(count($data))
        ->pluck($field)->toArray()->toEqual($data);
})->with([
    ['comments', Comment::class, 'comment', ['I comment!']],
    ['comments', Comment::class, 'comment', ['I comment!', 'And me too!']],
    ['friends', User::class, 'name', ['Alice']],
    ['friends', User::class, 'name', ['Alice', 'Bob']],
]);

test('Factories can attach to-one relationships with custom factory instances', function (string $relation, string $class, string $field, string $value) {
    $entity = Factory::new(User::class)
        ->has($relation, Factory::new($class)->state([$field => 'will be overridden']))
        ->has($relation, Factory::new($class)->state([$field => $value]))
        ->create();
    expect($entity->{$relation})->toBeInstanceOf($class)
        ->{$field}->toBe($value);
})->with([
    ['personalDetails', PersonalDetails::class, 'email', 'jane@doe.no'],
]);

test('Factories can attach to-many relationships with default factory instances', function (string $relation, string $class, int $count) {
    $factory = Factory::new(User::class);
    for ($i = 0; $i < $count; $i++) {
        $factory->has($relation);
    }
    $entity = $factory->create();
    expect($entity->{$relation})->toHaveCount($count);
})->with([
    ['comments', Comment::class, 1],
    ['comments', Comment::class, 2],
    ['friends', User::class, 1],
    ['friends', User::class, 3],
]);

test('Factories can attach to-one relationships with default factory instances', function (string $relation, string $class, string $field) {
    $entity = Factory::new(User::class)
        ->has($relation, Factory::new($class)->state([$field => 'will be overridden']))
        ->has($relation, Factory::new($class)->state())
        ->create();
    expect($entity->{$relation})->toBeInstanceOf($class)
        ->{$field}->not->toBe('will be overridden');
})->with([
    ['personalDetails', PersonalDetails::class, 'email'],
]);

test('Factory has method guards against invalid relations', function () {
    expect(fn() => Factory::new(User::class)->has('invalid'))->toThrow(
        InvalidRelationException::class,
        'Invalid relation used in "has" method [invalid]'
    );
});

test('Factories allow resetting of has method', function () {
    $entity = Factory::new(User::class)
        ->has('friends')
        ->has('comments')
        ->has('comments')
        ->has('posts')
        ->without('friends')
        ->without('comments')
        ->has('comments')
        ->has('comments')
        ->has('comments')
        ->create();
    expect(isset($entity->friends))->toBe(false)
        ->and($entity->posts)->toHaveCount(1)
        ->and($entity->comments)->toHaveCount(3);
});
