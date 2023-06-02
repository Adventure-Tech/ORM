<?php

use AdventureTech\ORM\Mapping\Linkers\HasOneLinker;
use AdventureTech\ORM\Mapping\Relations\HasOne;

test('The has one relation allows customizing the foreign key', function () {
    $instance = new HasOne('foreign_key');
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    expect($linker)->toBeInstanceOf(HasOneLinker::class)
        ->getTargetEntity()->toBe('TargetType')
        ->and(getProperty($linker, 'originEntity'))->toBe('OriginType')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'foreignKey'))->toBe('foreign_key');
});

test('The has one relation can infer the foreign key from the property type', function () {
    $instance = new HasOne();
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    // TODO: what should this be inferred from:
    //       - the type of the property
    //       - the table name of the entity
    expect($linker)->toBeInstanceOf(HasOneLinker::class)
        ->getTargetEntity()->toBe('TargetType')
        ->and(getProperty($linker, 'originEntity'))->toBe('OriginType')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'foreignKey'))->toBe('origin_type_id');
});
