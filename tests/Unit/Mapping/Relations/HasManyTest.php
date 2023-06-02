<?php

use AdventureTech\ORM\Mapping\Linkers\HasManyLinker;
use AdventureTech\ORM\Mapping\Relations\HasMany;

test('The has many relation allows customizing the foreign key', function () {
    $instance = new HasMany('TargetEntity', 'foreign_key');
    $linker = $instance->getLinker('propertyName', 'Collection', 'OriginType');

    expect($linker)->toBeInstanceOf(HasManyLinker::class)
        ->getTargetEntity()->toBe('TargetEntity')
        ->and(getProperty($linker, 'originEntity'))->toBe('OriginType')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'foreignKey'))->toBe('foreign_key');
});

test('The has many relation can infer the foreign key from the property type', function () {
    $instance = new HasMany('TargetEntity');
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    // TODO: what should this be inferred from:
    //       - the type of the property
    //       - the table name of the entity
    expect($linker)->toBeInstanceOf(HasManyLinker::class)
        ->getTargetEntity()->toBe('TargetEntity')
        ->and(getProperty($linker, 'originEntity'))->toBe('OriginType')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'foreignKey'))->toBe('origin_type_id');
});
