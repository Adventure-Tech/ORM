<?php

use AdventureTech\ORM\Mapping\Linkers\BelongsToManyLinker;
use AdventureTech\ORM\Mapping\Relations\BelongsToMany;

test('The belongs to many relation allows customizing the foreign key', function () {
    $instance = new BelongsToMany('TargetEntity', 'pivot_table', 'origin_foreign_key', 'target_foreign_key');
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    expect($linker)->toBeInstanceOf(BelongsToManyLinker::class)
        ->getTargetEntity()->toBe('TargetEntity')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'pivotTable'))->toBe('pivot_table')
        ->and(getProperty($linker, 'originForeignKey'))->toBe('origin_foreign_key')
        ->and(getProperty($linker, 'targetForeignKey'))->toBe('target_foreign_key');
});

test('The belongs to many relation can infer the foreign key from the property type', function () {
    $instance = new BelongsToMany('TargetEntity', 'pivot_table');
    $linker = $instance->getLinker('propertyName', 'Collection', 'OriginType');

    // TODO: what should this be inferred from:
    //       - the type of the property
    //       - the property name
    //       - the table name of the entity
    expect($linker)->toBeInstanceOf(BelongsToManyLinker::class)
        ->getTargetEntity()->toBe('TargetEntity')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName')
        ->and(getProperty($linker, 'pivotTable'))->toBe('pivot_table')
        ->and(getProperty($linker, 'originForeignKey'))->toBe('origin_type_id')
        ->and(getProperty($linker, 'targetForeignKey'))->toBe('target_entity_id');
});
