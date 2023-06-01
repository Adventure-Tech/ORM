<?php

use AdventureTech\ORM\Mapping\SoftDeletes\DeletedAt;

test('Deleted At annotation return current datetime', function () {
    $instance = new DeletedAt();
    expect($instance->getDatetime()->toIso8601String())->toBe(now()->toIso8601String());
});
