<?php

use AdventureTech\ORM\Exceptions\NotInitializedException;
use AdventureTech\ORM\Mapping\Entity;
use AdventureTech\ORM\Repository\Repository;

test('table name is correctly inferred from class name', function () {
    $entity = new Entity();
    $entity->initialize('class');
    expect($entity->getTable())->toBe('classes');
});

test('table name can be set via constructor', function () {
    $entity = new Entity('table_name');
    $entity->initialize('class');
    expect($entity->getTable())->toBe('table_name');
});

test('repository is null if not set in constructor', function () {
    $entity = new Entity();
    $entity->initialize('class');
    expect($entity->getRepository())->toBeNull();
});

test('repository can be set in constructor', function () {
    $entity = new Entity('table_name', 'Repository');
    $entity->initialize('class');
    expect($entity->getRepository())->toBe('Repository');
});

test('not initialising entity throws exception', function () {
    $entity = new Entity();
    expect(fn() => $entity->getTable())->toThrow(
        NotInitializedException::class,
        'Must initialize before using: AdventureTech\ORM\Mapping\Entity'
    )
        ->and(fn() => $entity->getRepository())->toThrow(
            NotInitializedException::class,
            'Must initialize before using: AdventureTech\ORM\Mapping\Entity'
        );
});
