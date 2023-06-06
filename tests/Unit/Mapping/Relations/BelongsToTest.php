<?php

use AdventureTech\ORM\Mapping\Linkers\BelongsToLinker;
use AdventureTech\ORM\Mapping\Relations\BelongsTo;
use Illuminate\Support\Str;

test('The belongs to relation allows customizing the foreign key', function () {
    $instance = new BelongsTo('foreign_key');
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    expect($linker)->toBeInstanceOf(BelongsToLinker::class)
        ->getTargetEntity()->toBe('TargetType')
        ->getForeignKey()->toBe('foreign_key')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName');
});

test('The belongs to relation can infer the foreign key from the property type', function () {
    $instance = new BelongsTo();
    $linker = $instance->getLinker('propertyName', 'TargetType', 'OriginType');

    // TODO: what should this be inferred from:
    //       - the type of the property
    //       - the property name
    //       - the table name of the entity
    expect($linker)->toBeInstanceOf(BelongsToLinker::class)
        ->getTargetEntity()->toBe('TargetType')
        ->getForeignKey()->toBe('target_type_id')
        ->and(getProperty($linker, 'relation'))->toBe('propertyName');
});
